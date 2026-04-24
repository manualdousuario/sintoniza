<?php

declare(strict_types=1);

namespace Sintoniza\Api;

use Exception;
use InvalidArgumentException;
use Monolog\Logger as MonologLogger;
use Sintoniza\Database\DB;
use Sintoniza\Library\Url;

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
            $userId    = (int) $this->api->getUser()->id;

            $valid = [];
            foreach ($input as $action) {
                try {
                    $this->validateEpisodeAction($action);
                    $action->podcast = Url::normalizeFeed($action->podcast);
                    $action->episode = Url::normalize($action->episode);
                    $valid[] = $action;
                } catch (InvalidArgumentException) {
                    continue;
                }
            }

            if (empty($valid)) {
                $this->db->commit();
                return ['timestamp' => $timestamp, 'update_urls' => []];
            }

            $subByUrl = $this->loadOrCreateSubscriptions($valid, $userId, $timestamp);
            $episodes = $this->loadEpisodeIds($valid, $subByUrl);

            foreach ($valid as $action) {
                $sub        = $subByUrl[$action->podcast];
                $episode_id = $sub['feed'] !== null
                    ? ($episodes[$sub['feed'] . ':' . $action->episode] ?? null)
                    : null;

                $device_id = null;
                if (!empty($action->device)) {
                    $device_id = $this->api->getDeviceID($action->device, $userId);
                }

                $actionData = clone $action;
                unset($actionData->action, $actionData->episode, $actionData->podcast, $actionData->device);

                $this->db->simple(
                    'INSERT IGNORE INTO episodes_actions (user, subscription, url, episode, changed, action, data, device)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    $userId,
                    $sub['id'],
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

    private function loadOrCreateSubscriptions(array $actions, int $userId, int $timestamp): array
    {
        $urls = array_values(array_unique(array_map(fn($a) => $a->podcast, $actions)));
        $placeholders = implode(',', array_fill(0, count($urls), '?'));

        $rows = $this->db->all(
            "SELECT id, url, feed FROM subscriptions WHERE user = ? AND url IN ($placeholders)",
            $userId,
            ...$urls
        );

        $map = [];
        foreach ($rows as $row) {
            $map[$row->url] = [
                'id'   => (int) $row->id,
                'feed' => $row->feed !== null ? (int) $row->feed : null,
            ];
        }

        foreach ($urls as $url) {
            if (!isset($map[$url])) {
                $this->db->simple(
                    'INSERT INTO subscriptions (user, url, changed) VALUES (?, ?, ?)',
                    $userId,
                    $url,
                    $timestamp
                );
                $map[$url] = ['id' => (int) $this->db->lastInsertId(), 'feed' => null];
            }
        }

        return $map;
    }

    private function loadEpisodeIds(array $actions, array $subByUrl): array
    {
        $byFeed = [];
        foreach ($actions as $action) {
            $feedId = $subByUrl[$action->podcast]['feed'] ?? null;
            if ($feedId === null) {
                continue;
            }
            $byFeed[$feedId][$action->episode] = true;
        }

        $map = [];
        foreach ($byFeed as $feedId => $urlSet) {
            $urls         = array_keys($urlSet);
            $placeholders = implode(',', array_fill(0, count($urls), '?'));
            $rows = $this->db->all(
                "SELECT id, media_url FROM episodes WHERE feed = ? AND media_url IN ($placeholders)",
                $feedId,
                ...$urls
            );
            foreach ($rows as $row) {
                $map[$feedId . ':' . $row->media_url] = (int) $row->id;
            }
        }

        return $map;
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
