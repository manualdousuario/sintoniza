<?php

declare(strict_types=1);

namespace Sintoniza\Cli;

use League\CLImate\CLImate;
use Sintoniza\Service\FeedService;

class UpdateFeeds
{
    public function __construct(
        private FeedService $feedService,
        private CLImate $climate
    ) {}

    public function run(?int $maxFeeds = null): void
    {
        $this->climate->bold('Sintoniza — Atualizando feeds...');

        $count = $this->feedService->updateAllStaleFeeds(cli: true, maxFeeds: $maxFeeds);

        $this->climate->green()->bold(sprintf(
            'Concluído: %d feed(s) atualizados em %s',
            $count,
            date('Y-m-d H:i:s')
        ));
    }
}
