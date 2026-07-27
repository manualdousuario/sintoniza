<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FeedService;
use Illuminate\Console\Command;

class FetchFeeds extends Command
{
    protected $signature = 'sintoniza:fetch {urls?* : One or more feed URLs}';

    protected $description = 'Fetch and sync one or more feeds immediately';

    public function handle(FeedService $feeds): int
    {
        /** @var array<int, string> $urls */
        $urls = $this->argument('urls');

        if ($urls === []) {
            $this->error('Usage: sintoniza:fetch <url> [url...]');

            return self::INVALID;
        }

        $failed = 0;

        foreach ($urls as $url) {
            $this->line("Fetching {$url}");

            if ($feeds->fetchAndSync($url)) {
                $this->info('  -> Synced');
            } else {
                $this->error('  -> Failed');
                $failed++;
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
