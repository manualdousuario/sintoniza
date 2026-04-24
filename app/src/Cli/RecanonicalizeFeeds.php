<?php

declare(strict_types=1);

namespace Sintoniza\Cli;

use League\CLImate\CLImate;
use Sintoniza\Service\FeedService;

class RecanonicalizeFeeds
{
    public function __construct(
        private FeedService $feedService,
        private CLImate $climate
    ) {}

    public function run(?int $limit = null, int $sleepMs = 0): void
    {
        $this->climate->bold('Sintoniza — Recanonicalize feeds...');

        $count = $this->feedService->recanonicalizeAll(
            cli:     true,
            limit:   $limit,
            sleepMs: $sleepMs
        );

        $this->climate->green()->bold(sprintf(
            'Concluído: %d feed(s) processados em %s',
            $count,
            date('Y-m-d H:i:s')
        ));
    }
}
