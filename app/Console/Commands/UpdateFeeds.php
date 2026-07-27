<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FeedService;
use Illuminate\Console\Command;

class UpdateFeeds extends Command
{
    protected $signature = 'sintoniza:update-feeds
                            {--max-feeds= : Maximum number of feeds to process}
                            {--limit= : Alias of --max-feeds}';

    protected $description = 'Update stale feeds (respect next_fetch_at)';

    public function handle(FeedService $feeds): int
    {
        $maxFeeds = $this->option('max-feeds') ?? $this->option('limit');
        $maxFeeds = $maxFeeds !== null ? (int) $maxFeeds : null;

        if ($maxFeeds !== null && $maxFeeds <= 0) {
            $maxFeeds = null;
        }

        $this->info('Sintoniza — updating stale feeds...');

        $count = $feeds->updateAllStaleFeeds($maxFeeds);

        $this->info(sprintf('Done: %d feed(s) updated at %s', $count, now()->toDateTimeString()));

        return self::SUCCESS;
    }
}
