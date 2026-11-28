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
     * Non-admin users with no activity (web or gPodder API) for this many
     * months are deleted with all their data. 0 (default) never deletes.
     */
    'inactive_user_months' => (int) env('INACTIVE_USER_MONTHS', 0),

    // Available UI locales; each needs a lang/{locale}/sintoniza.php file.
    'locales' => ['en', 'es', 'pt_BR'],

];
