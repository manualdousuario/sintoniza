<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gpodder;

use App\Support\Gpodder;
use App\Support\Url;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

class EpisodesController
{
    private const ALLOWED_ACTIONS = ['download', 'play', 'delete', 'new'];

    private const INDEX_CHUNK_SIZE = 1000;

    public function index(Request $request, ?string $username = null): StreamedJsonResponse
    {
        $timestamp = time();
        $since = Carbon::createFromTimestamp((int) $request->query('since', 0), 'UTC');
        $userId = Auth::id();

        $actions = function () use ($userId, $since) {
            $rows = DB::table('episode_actions as e')
                ->join('subscriptions as s', 's.id', '=', 'e.subscription_id')
                ->where('e.user_id', $userId)
                ->where('e.changed_at', '>=', $since)
                ->select(['e.id', 'e.url as episode', 'e.action', 'e.data', 's.url as podcast', 'e.changed_at'])
                ->lazyById(self::INDEX_CHUNK_SIZE, 'e.id', 'id');

            foreach ($rows as $row) {
                $action = Gpodder::mergeDataRow([
                    'episode' => $row->episode,
                    'action' => $row->action,
                    'data' => $row->data,
                    'podcast' => $row->podcast,
                    'timestamp' => Carbon::parse($row->changed_at, 'UTC')->format('Y-m-d\TH:i:s\Z'),
                ]);

                if ($action !== null) {
                    yield $action;
                }
            }
        };

        return response()->streamJson([
            'timestamp' => $timestamp,
            'actions' => $actions(),
        ], 200, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function store(Request $request, ?string $username = null): JsonResponse
    {
        $input = json_decode($request->getContent() ?: 'null');

        if ($request->getContent() && json_last_error() !== JSON_ERROR_NONE) {
            return Gpodder::error(400, __('sintoniza.messages.invalid_json'));
        }

        if (! is_array($input)) {
            return Gpodder::error(400, __('sintoniza.messages.invalid_array'));
        }

        $timestamp = time();
        $userId = (int) Auth::id();

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

        if ($valid === []) {
            return response()->json(['timestamp' => $timestamp, 'update_urls' => []], 200, [], JSON_PRETTY_PRINT);
        }

        DB::transaction(function () use ($valid, $userId, $timestamp) {
            $subByUrl = $this->loadOrCreateSubscriptions($valid, $userId, $timestamp);
            $deviceIdMap = $this->loadDeviceIds($valid, $userId);

            foreach ($valid as $action) {
                $subscriptionId = $subByUrl[$action->podcast];

                $deviceId = null;
                if (! empty($action->device)) {
                    // A malformed device id fails the whole batch, unlike the
                    // per-action validation below which just skips the action.
                    if ($error = Gpodder::validatePattern($action->device, 'deviceid', 'device_id')) {
                        throw new HttpResponseException($error);
                    }

                    $deviceId = $deviceIdMap[$action->device] ?? null;
                }

                $actionData = clone $action;
                unset($actionData->action, $actionData->episode, $actionData->podcast, $actionData->device);

                $changedAt = ! empty($action->timestamp)
                    ? Carbon::parse($action->timestamp)->utc()
                    : Carbon::createFromTimestamp($timestamp, 'UTC');

                DB::table('episode_actions')->insertOrIgnore([
                    'user_id' => $userId,
                    'subscription_id' => $subscriptionId,
                    'url' => $action->episode,
                    'device_id' => $deviceId,
                    'changed_at' => $changedAt->toDateTimeString(),
                    'action' => strtolower($action->action),
                    'data' => json_encode($actionData, JSON_THROW_ON_ERROR),
                    'created_at' => Carbon::createFromTimestamp($timestamp, 'UTC')->toDateTimeString(),
                    'updated_at' => Carbon::createFromTimestamp($timestamp, 'UTC')->toDateTimeString(),
                ]);
            }
        });

        return response()->json(['timestamp' => $timestamp, 'update_urls' => []], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Maps podcast URL => subscription id. Clients may report actions for
     * podcasts they never explicitly subscribed to, so missing subscriptions
     * are created on the fly.
     *
     * @return array<string, int>
     */
    private function loadOrCreateSubscriptions(array $actions, int $userId, int $timestamp): array
    {
        $urls = array_values(array_unique(array_map(fn ($a) => $a->podcast, $actions)));

        $now = Carbon::createFromTimestamp($timestamp, 'UTC')->toDateTimeString();

        DB::table('subscriptions')->insertOrIgnore(array_map(fn (string $url) => [
            'user_id' => $userId,
            'url' => $url,
            'created_at' => $now,
            'updated_at' => $now,
        ], $urls));

        return DB::table('subscriptions')
            ->where('user_id', $userId)
            ->whereIn('url', $urls)
            ->pluck('id', 'url')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Pre-load all referenced device identifiers into a map to avoid N+1 queries.
     *
     * @return array<string, int>
     */
    private function loadDeviceIds(array $actions, int $userId): array
    {
        $identifiers = array_values(array_unique(array_filter(
            array_map(fn ($a) => $a->device ?? null, $actions)
        )));

        if ($identifiers === []) {
            return [];
        }

        return DB::table('devices')
            ->where('user_id', $userId)
            ->whereIn('identifier', $identifiers)
            ->pluck('id', 'identifier')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function validateEpisodeAction(object $action): void
    {
        if (! isset($action->podcast, $action->action, $action->episode)) {
            throw new InvalidArgumentException(__('sintoniza.messages.missing_action_key'));
        }

        if (! in_array(strtolower((string) $action->action), self::ALLOWED_ACTIONS, true)) {
            throw new InvalidArgumentException(__('sintoniza.messages.invalid_action'));
        }

        if (! Gpodder::validateUrl((string) $action->podcast) || ! Gpodder::validateUrl((string) $action->episode)) {
            throw new InvalidArgumentException(__('sintoniza.messages.invalid_url'));
        }

        if (! empty($action->timestamp)) {
            // A malformed timestamp fails the whole batch.
            if ($error = Gpodder::validatePattern((string) $action->timestamp, 'timestamp', 'timestamp')) {
                throw new HttpResponseException($error);
            }
        }
    }
}
