<?php

declare(strict_types=1);

namespace Sintoniza\Api;

use JsonException;
use Monolog\Logger as MonologLogger;
use Sintoniza\Database\DB;
use stdClass;

class GpodderDevicesHandler
{
    public function __construct(
        private GpodderApi $api,
        private DB $db,
        private MonologLogger $logger
    ) {}

    public function handle(): array
    {
        $method = $this->api->getMethod();

        if ($method === 'GET') {
            return $this->api->queryWithData(
                'SELECT deviceid as id, user, deviceid, name, data FROM devices WHERE user = ?',
                $this->api->getUser()->id
            );
        }

        if ($method === 'POST') {
            $deviceid = explode('/', $this->api->getPath())[1] ?? null;

            if (!$deviceid) {
                $this->api->error(400, __('messages.invalid_device_id'));
            }

            $this->api->validatePattern($deviceid, 'deviceid', 'device_id');

            $json = $this->api->getInput();
            $json ??= new stdClass();
            $json->subscriptions = 0;

            $this->db->upsert('devices', [
                'deviceid' => $deviceid,
                'data'     => json_encode($json, JSON_THROW_ON_ERROR),
                'name'     => $json->caption ?? null,
                'user'     => $this->api->getUser()->id,
            ], ['deviceid', 'user']);

            $this->api->error(200, __('messages.device_updated'));
        }

        $this->api->error(400, __('messages.invalid_request_method'));
        return [];
    }
}
