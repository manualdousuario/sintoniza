<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gpodder;

use App\Models\Device;
use App\Support\Gpodder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevicesController
{
    public function index(Request $request, string $username): JsonResponse
    {
        $rows = Device::where('user_id', Auth::id())
            ->get(['identifier', 'user_id', 'name', 'data'])
            ->map(fn (Device $device) => (object) [
                'id' => $device->identifier,
                'user' => $device->user_id,
                'deviceid' => $device->identifier,
                'name' => $device->name,
                'data' => $device->data !== null ? json_encode($device->data) : null,
            ]);

        return response()->json(Gpodder::mergeDataRows($rows), 200, [], JSON_PRETTY_PRINT);
    }

    public function update(Request $request, string $username, string $deviceid): JsonResponse
    {
        if ($deviceid === '') {
            return Gpodder::error(400, __('sintoniza.messages.invalid_device_id'));
        }

        if ($error = Gpodder::validatePattern($deviceid, 'deviceid', 'device_id')) {
            return $error;
        }

        $json = json_decode($request->getContent() ?: 'null');
        $json ??= new \stdClass;
        $json->subscriptions = 0;

        Device::updateOrCreate(
            ['identifier' => $deviceid, 'user_id' => Auth::id()],
            [
                'name' => $json->caption ?? null,
                'data' => $json,
            ]
        );

        return Gpodder::error(200, __('sintoniza.messages.device_updated'));
    }
}
