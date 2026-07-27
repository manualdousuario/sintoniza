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

class EpisodesController
{
    private const ALLOWED_ACTIONS = ['download', 'play', 'delete', 'new'];

    public function index(Request $request, ?string $username = null): JsonResponse
    {
        $since = Carbon::createFromTimestamp((int) $request->query('since', 0), 'UTC');

        $rows = DB::table('episode_actions as e')
            ->join('subscriptions as s', 's.id', '=', 'e.subscription_id')
            ->where('e.user_id', Auth::id())
            ->where('e.changed_at', '>=', $since)
            ->get(['e.url as episode', 'e.action', 'e.data', 's.url as podcast', 'e.changed_at'])
            ->map(fn (object $row) => (object) [
                'episode' => $row->episode,
                'action' => $row->action,
                'data' => $row->data,
                'podcast' => $row->podcast,
                'timestamp' => Carbon::parse($row->changed_at, 'UTC')->format('Y-m-d\TH:i:s\Z'),
            ]);

        return response()->json([
            'timestamp' => time(),
            'actions' => Gpodder::mergeDataRows($rows),
        ], 200, [], JSON_PRETTY_PRINT);
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
            $episodes = $this->loadEpisodeIds($valid, $subByUrl);
            $deviceIdMap = $this->loadDeviceIds($valid, $userId);

            foreach ($valid as $action) {
                $sub = $subByUrl[$action->podcast];
                $episodeId = $sub['feed'] !== null
                    ? ($episodes[$sub['feed'].':'.$action->episode] ?? null)
                    : null;

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
                    'subscription_id' => $sub['id'],
                    'url' => $action->episode,
                    'episode_id' => $episodeId,
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
     * Maps podcast URL => ['id' => subscription id, 'feed' => feed id|null].
     * Clients may report actions for podcasts they never explicitly subscribed
     * to, so missing subscriptions are created on the fly.
     */
    private function loadOrCreateSubscriptions(array $actions, int $userId, int $timestamp): array
    {
        $urls = array_values(array_unique(array_map(fn ($a) => $a->podcast, $actions)));

        $rows = DB::table('subscriptions')
            ->where('user_id', $userId)
            ->whereIn('url', $urls)
            ->get(['id', 'url', 'feed_id']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->url] = ['id' => (int) $row->id, 'feed' => $row->feed_id !== null ? (int) $row->feed_id : null];
        }

        $now = Carbon::createFromTimestamp($timestamp, 'UTC')->toDateTimeString();

        foreach ($urls as $url) {
            if (isset($map[$url])) {
                continue;
            }

            DB::table('subscriptions')->insertOrIgnore([
                'user_id' => $userId,
                'url' => $url,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $existing = DB::table('subscriptions')
                ->where('user_id', $userId)
                ->where('url', $url)
                ->first(['id', 'feed_id']);

            if ($existing) {
                $map[$url] = [
                    'id' => (int) $existing->id,
                    'feed' => $existing->feed_id !== null ? (int) $existing->feed_id : null,
                ];
            }
        }

        return $map;
    }

    /**
     * Map "{feedId}:{mediaUrl}" => episode id for all referenced episodes.
     */
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
            $rows = DB::table('episodes')
                ->where('feed_id', $feedId)
                ->whereIn('media_url', array_keys($urlSet))
                ->get(['id', 'media_url']);

            foreach ($rows as $row) {
                $map[$feedId.':'.$row->media_url] = (int) $row->id;
            }
        }

        return $map;
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
