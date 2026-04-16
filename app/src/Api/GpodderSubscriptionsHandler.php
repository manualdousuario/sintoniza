<?php

declare(strict_types=1);

namespace Sintoniza\Api;

use Exception;
use Monolog\Logger as MonologLogger;
use Sintoniza\Database\DB;

class GpodderSubscriptionsHandler
{
    public function __construct(
        private GpodderApi $api,
        private DB $db,
        private MonologLogger $logger
    ) {}

    public function handle(): mixed
    {
        $method   = $this->api->getMethod();
        $v2       = str_contains($this->api->url, 'api/2/');
        $deviceid = explode('/', $this->api->getPath())[1] ?? null;

        if ($method === 'GET' && !$v2) {
            return $this->db->rowsFirstColumn(
                'SELECT url FROM subscriptions WHERE user = ?',
                $this->api->getUser()->id
            );
        }

        if (!$deviceid) {
            $this->api->error(400, __('messages.invalid_device_id'));
        }

        $this->api->validatePattern($deviceid, 'deviceid', 'device_id');

        if ($v2 && $method === 'GET') {
            $timestamp = (int) ($_GET['since'] ?? 0);

            return [
                'add'         => $this->db->rowsFirstColumn('SELECT url FROM subscriptions WHERE user = ? AND deleted = 0 AND changed >= ?', $this->api->getUser()->id, $timestamp),
                'remove'      => $this->db->rowsFirstColumn('SELECT url FROM subscriptions WHERE user = ? AND deleted = 1 AND changed >= ?', $this->api->getUser()->id, $timestamp),
                'update_urls' => [],
                'timestamp'   => time(),
            ];
        }

        if ($method === 'PUT') {
            $lines = $this->api->getInput();

            if (!is_array($lines)) {
                $this->api->error(400, __('messages.invalid_input_array'));
            }

            try {
                $this->db->beginTransaction();
                $ts = time();

                foreach ($lines as $url) {
                    if (!$this->api->validateURL($url)) {
                        continue;
                    }
                    $this->db->simple(
                        'INSERT IGNORE INTO subscriptions (user, url, changed) VALUES (?, ?, ?)',
                        $this->api->getUser()->id,
                        $url,
                        $ts
                    );
                }

                $this->db->commit();
                return null;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        if ($method === 'POST') {
            $input = $this->api->getInput();

            try {
                $this->db->beginTransaction();
                $ts = time();

                if (!empty($input->add) && is_array($input->add)) {
                    foreach ($input->add as $url) {
                        if (!$this->api->validateURL($url)) continue;
                        $this->db->upsert('subscriptions', ['user' => $this->api->getUser()->id, 'url' => $url, 'changed' => $ts, 'deleted' => 0], ['user', 'url']);
                    }
                }

                if (!empty($input->remove) && is_array($input->remove)) {
                    foreach ($input->remove as $url) {
                        if (!$this->api->validateURL($url)) continue;
                        $this->db->upsert('subscriptions', ['user' => $this->api->getUser()->id, 'url' => $url, 'changed' => $ts, 'deleted' => 1], ['user', 'url']);
                    }
                }

                $this->db->commit();
                return ['timestamp' => $ts, 'update_urls' => []];
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        $this->api->error(501, __('messages.not_implemented'));
        return null;
    }
}
