<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sintoniza configuration
    |--------------------------------------------------------------------------
    |
    | The environment variable names here are a compatibility surface: existing
    | Docker deployments set them, so they must not be renamed.
    |
    */

    /*
     * Allow public user registration. The very first user is always
     * allowed (and becomes admin), even when this is disabled.
     */
    'enable_subscriptions' => env('ENABLE_SUBSCRIPTIONS', false),

    /*
     * When true, newly subscribed feeds that were never fetched are
     * dispatched to the queue immediately instead of waiting for the
     * hourly scheduler run.
     */
    'immediate_feed_fetch' => env('IMMEDIATE_FEED_FETCH', true),

    'feeds' => [
        // Seconds between two fetches of the same feed.
        'fetch_interval' => 86400,

        // Each hourly run processes total/12 feeds, so the whole catalog is
        // refreshed roughly every 12 hours.
        'batch_divisor' => 12,
        'min_batch' => 100,

        // Per-feed processing time limit (seconds).
        'per_feed_time_limit' => 30,

        // Episodes are sent in bulk upserts of this size.
        'upsert_chunk' => 200,
    ],

    'podcastindex' => [
        'key' => env('PODCAST_INDEX_API_KEY', ''),
        'secret' => env('PODCAST_INDEX_API_SECRET', ''),
        'use_as_primary' => env('PODCAST_INDEX_USE_AS_PRIMARY', false),
        'fallback_to_rss' => env('PODCAST_INDEX_FALLBACK_TO_RSS', true),
        'base_uri' => env('PODCAST_INDEX_BASE_URI', 'https://api.podcastindex.org/api/1.0'),
    ],

    // Available UI locales; each needs a lang/{locale}/sintoniza.php file.
    'locales' => ['en', 'es', 'pt_BR'],

];
