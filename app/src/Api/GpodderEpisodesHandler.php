<?php

declare(strict_types=1);

namespace Sintoniza\Api;

use Exception;
use InvalidArgumentException;
use Monolog\Logger as MonologLogger;
use Sintoniza\Database\DB;

class GpodderEpisodesHandler
{
    private const ALLOWED_ACTIONS = ['download', 'play', 'delete', 'new'];

    public function __construct(
        private GpodderApi $api,
        private DB $db,
        private MonologLogger $logger
    ) {}

    public function handle(): array
    {
        if ($this->api->getMethod() === 'GET') {
            $since = isset($_GET['since']) ? (int) $_GET['since'] : 0;

            return [
                'timestamp' => time(),
                'actions'   => $this->api->queryWithData(
                    'SELECT e.url AS episode, e.action, e.data, s.url AS podcast,
                     DATE_FORMAT(FROM_UNIXTIME(e.changed), "%Y-%m-%dT%H:%i:%sZ") AS timestamp
                     FROM episodes_actions e
                     INNER JOIN subscriptions s ON s.id = e.subscription
                     WHERE e.user = ? AND e.changed >= ?',
                    $this->api->getUser()->id,
                    $since
                ),
            ];
        }

        $this->api->requireMethod('POST');

        $input = $this->api->getInput();

        if (!is_array($input)) {
            $this->api->error(400, __('messages.invalid_array'));
        }

        try {
            $this->db->beginTransaction();
            $timestamp = time();

            foreach ($input as $action) {
                try {
                    $this->validateEpisodeAction($action);
                } catch (InvalidArgumentException) {
                    continue;
                }

                $subscription_id = $this->db->firstColumn(
                    'SELECT id FROM subscriptions WHERE url = ? AND user = ?',
                    $action->podcast,
                    $this->api->getUser()->id
                );

                if (!$subscription_id) {
                    $this->db->simple('INSERT INTO subscriptions (user, url, changed) VALUES (?, ?, ?)', $this->api->getUser()->id, $action->podcast, $timestamp);
                    $subscription_id = $this->db->lastInsertId();
                }

                $feed_id    = $this->db->firstColumn('SELECT feed FROM subscriptions WHERE id = ?', $subscription_id);
                $episode_id = null;

                if ($feed_id) {
                    $episode_id = $this->db->firstColumn('SELECT id FROM episodes WHERE media_url = ? AND feed = ?', $action->episode, $feed_id);
                }

                $device_id = null;
                if (!empty($action->device)) {
                    $device_id = $this->api->getDeviceID($action->device, $this->api->getUser()->id);
                }

                $actionData = clone $action;
                unset($actionData->action, $actionData->episode, $actionData->podcast, $actionData->device);

                $this->db->simple(
                    'INSERT INTO episodes_actions (user, subscription, url, episode, changed, action, data, device)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    $this->api->getUser()->id,
                    $subscription_id,
                    $action->episode,
                    $episode_id,
                    !empty($action->timestamp) ? strtotime($action->timestamp) : $timestamp,
                    strtolower($action->action),
                    json_encode($actionData, JSON_THROW_ON_ERROR),
                    $device_id
                );
            }

            $this->db->commit();
            return ['timestamp' => $timestamp, 'update_urls' => []];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateEpisodeAction(object $action): void
    {
        if (!isset($action->podcast, $action->action, $action->episode)) {
            throw new InvalidArgumentException(__('messages.missing_action_key'));
        }

        if (!in_array(strtolower($action->action), self::ALLOWED_ACTIONS)) {
            throw new InvalidArgumentException(__('messages.invalid_action'));
        }

        if (!$this->api->validateURL($action->podcast) || !$this->api->validateURL($action->episode)) {
            throw new InvalidArgumentException(__('messages.invalid_url'));
        }

        if (!empty($action->timestamp)) {
            $this->api->validatePattern($action->timestamp, 'timestamp', 'timestamp');
        }
    }
}
