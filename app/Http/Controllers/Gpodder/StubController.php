<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gpodder;

use App\Support\Gpodder;
use Illuminate\Http\JsonResponse;

/** Unimplemented gPodder endpoints that clients still expect to answer. */
class StubController
{
    public function emptyOrUnavailable(string $section): JsonResponse
    {
        if (in_array($section, ['tag', 'tags', 'data', 'toplist', 'suggestions', 'favorites'], true)) {
            return response()->json([], 200, [], JSON_PRETTY_PRINT);
        }

        return Gpodder::error(503, __('sintoniza.messages.not_implemented'));
    }

    public function empty(): JsonResponse
    {
        return response()->json([], 200, [], JSON_PRETTY_PRINT);
    }

    public function notImplemented(): JsonResponse
    {
        return Gpodder::error(501, __('sintoniza.messages.not_implemented'));
    }

    public function formatNotImplemented(): JsonResponse
    {
        return Gpodder::error(501, __('sintoniza.messages.output_format_not_implemented'));
    }
}
