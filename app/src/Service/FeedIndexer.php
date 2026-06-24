<?php

declare(strict_types=1);

namespace Sintoniza\Service;

use Sintoniza\Database\DB;
use Sintoniza\Library\Url;

class FeedIndexer
{
    public function __construct(private DB $db) {}

    /**
     * @param string[] $urls
     */
    public function dispatchNew(array $urls): void
    {
        if (!IMMEDIATE_FEED_FETCH) {
            return;
        }

        $new = [];

        foreach ($urls as $url) {
            $normalized = Url::normalizeFeed((string) $url);

            if ($normalized === '' || isset($new[$normalized])) {
                continue;
            }

            if ($this->exists($normalized)) {
                continue;
            }

            $new[$normalized] = true;
        }

        if ($new === []) {
            return;
        }

        $this->spawn(array_keys($new));
    }

    private function exists(string $normalizedUrl): bool
    {
        return (bool) $this->db->firstColumn(
            'SELECT 1 FROM feeds WHERE feed_url = ?
             UNION
             SELECT 1 FROM feed_aliases WHERE url = ?
             LIMIT 1',
            $normalizedUrl,
            $normalizedUrl
        );
    }

    /**
     * @param string[] $urls
     */
    private function spawn(array $urls): void
    {
        $php = PHP_BINARY ?: 'php';
        $cli = APP_PATH . '/cli/sintoniza';

        $args = implode(' ', array_map('escapeshellarg', $urls));

        $cmd = sprintf(
            '%s %s fetch %s > /dev/null 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($cli),
            $args
        );

        @exec($cmd);
    }
}
