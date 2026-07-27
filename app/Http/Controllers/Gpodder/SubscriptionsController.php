<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gpodder;

use App\Models\Subscription;
use App\Services\FeedIndexer;
use App\Support\Gpodder;
use App\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionsController
{
    public function __construct(
        private FeedIndexer $feedIndexer
    ) {}

    /**
     * v1: plain list of the user's subscription URLs. Deliberately includes
     * soft-deleted rows — v1 clients expect the full set.
     */
    public function v1List(Request $request, string $username, string $format): JsonResponse|Response
    {
        $urls = DB::table('subscriptions')
            ->where('user_id', Auth::id())
            ->pluck('url')
            ->all();

        if ($format === 'opml') {
            return Gpodder::opml($urls);
        }

        // Protocol quirk: .txt also returns the JSON array.
        return response()->json($urls, 200, [], JSON_PRETTY_PRINT);
    }

    /** v2 delta: subscriptions added/removed since a unix timestamp. */
    public function delta(Request $request, ?string $username = null, ?string $deviceid = null): JsonResponse
    {
        if ($error = $this->validateDeviceId($deviceid)) {
            return $error;
        }

        $since = Carbon::createFromTimestamp((int) $request->query('since', 0), 'UTC');

        $add = DB::table('subscriptions')
            ->where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->where('updated_at', '>=', $since)
            ->pluck('url')
            ->all();

        $remove = DB::table('subscriptions')
            ->where('user_id', Auth::id())
            ->whereNotNull('deleted_at')
            ->where('updated_at', '>=', $since)
            ->pluck('url')
            ->all();

        return response()->json([
            'add' => $add,
            'remove' => $remove,
            'update_urls' => [],
            'timestamp' => time(),
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /** Bulk add: one URL per line in a text/plain body. */
    public function bulkAdd(Request $request, ?string $username = null, ?string $deviceid = null): Response|JsonResponse
    {
        if ($error = $this->validateDeviceId($deviceid)) {
            return $error;
        }

        $lines = array_filter(explode("\n", $request->getContent()), 'trim');

        $ts = now();
        $added = [];

        DB::transaction(function () use ($lines, $ts, &$added) {
            foreach ($lines as $url) {
                $url = trim($url);

                if (! Gpodder::validateUrl($url)) {
                    continue;
                }

                $normalized = Url::normalizeFeed($url);

                Subscription::subscribe((int) Auth::id(), $normalized, $ts);

                $added[] = $normalized;
            }
        });

        $this->feedIndexer->dispatchNew($added);

        return response()->json([], 200, [], JSON_PRETTY_PRINT);
    }

    /** Delta sync of a {add: [...], remove: [...]} body. */
    public function sync(Request $request, ?string $username = null, ?string $deviceid = null): JsonResponse
    {
        if ($error = $this->validateDeviceId($deviceid)) {
            return $error;
        }

        $input = json_decode($request->getContent() ?: 'null');

        if ($request->getContent() && json_last_error() !== JSON_ERROR_NONE) {
            return Gpodder::error(400, __('sintoniza.messages.invalid_json'));
        }

        $ts = now();
        $added = [];

        DB::transaction(function () use ($input, $ts, &$added) {
            if (! empty($input->add) && is_array($input->add)) {
                foreach ($input->add as $url) {
                    if (! Gpodder::validateUrl((string) $url)) {
                        continue;
                    }

                    $normalized = Url::normalizeFeed((string) $url);

                    Subscription::subscribe((int) Auth::id(), $normalized, $ts);

                    $added[] = $normalized;
                }
            }

            if (! empty($input->remove) && is_array($input->remove)) {
                foreach ($input->remove as $url) {
                    if (! Gpodder::validateUrl((string) $url)) {
                        continue;
                    }

                    Subscription::unsubscribe((int) Auth::id(), Url::normalizeFeed((string) $url), $ts);
                }
            }
        });

        $this->feedIndexer->dispatchNew($added);

        return response()->json([
            'timestamp' => $ts->getTimestamp(),
            'update_urls' => [],
        ], 200, [], JSON_PRETTY_PRINT);
    }

    private function validateDeviceId(?string $deviceid): ?JsonResponse
    {
        if (! $deviceid) {
            return Gpodder::error(400, __('sintoniza.messages.invalid_device_id'));
        }

        return Gpodder::validatePattern($deviceid, 'deviceid', 'device_id');
    }
}
