<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\FeedService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchFeed implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly string $url
    ) {}

    public function handle(FeedService $feeds): void
    {
        $feeds->fetchAndSync($this->url);
    }
}
