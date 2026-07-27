<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Feed;
use App\Support\Url;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

/**
 * RSS/Atom feed fetcher, parser and synchronizer.
 *
 * Parsing is deliberately separated from fetching: parse() takes a raw XML
 * string and needs no network, so it is unit-testable on its own; fetch()
 * performs the conditional HTTP GET and delegates to it.
 */
class FeedParser
{
    public ?string $feed_url = null;

    public ?string $image_url = null;

    public ?string $url = null;

    public ?string $language = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?DateTimeInterface $published_at = null;

    public ?DateTimeInterface $last_fetched_at = null;

    public ?DateTimeInterface $next_fetch_at = null;

    public ?string $etag = null;

    public ?string $last_modified = null;

    public bool $notModified = false;

    /** @var array<int, object> */
    protected array $episodes = [];

    /** URL as originally requested (before any canonical rewrite). */
    protected ?string $requested_url = null;

    /** URLs that should alias to this feed after sync(). */
    protected array $aliases = [];

    protected const NAMESPACES = [
        'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
        'content' => 'http://purl.org/rss/1.0/modules/content/',
        'media' => 'http://search.yahoo.com/mrss/',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'atom' => 'http://www.w3.org/2005/Atom',
    ];

    protected const MAX_DURATION = 86400;

    protected const MIN_DURATION = 20;

    public function __construct(string $url)
    {
        $this->feed_url = Url::normalizeFeed($url);
        $this->requested_url = $this->feed_url;
    }

    /** @return array<int, object> */
    public function listEpisodes(): array
    {
        return $this->episodes;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Conditional GET of the feed into a temp file, then parse.
     *
     * Sends If-None-Match / If-Modified-Since when $etag / $last_modified
     * are set (FeedService primes them from the stored feed row).
     */
    public function fetch(): bool
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sintoniza_feed_');
        if ($tmp === false) {
            return false;
        }

        $headers = [];
        if ($this->etag) {
            $headers['If-None-Match'] = $this->etag;
        }
        if ($this->last_modified) {
            $headers['If-Modified-Since'] = $this->last_modified;
        }

        $effectiveUri = null;

        try {
            $response = Http::withHeaders($headers)
                ->withUserAgent('Sintoniza')
                ->withOptions([
                    'sink' => $tmp,
                    'on_stats' => function (TransferStats $stats) use (&$effectiveUri) {
                        $effectiveUri = (string) $stats->getEffectiveUri();
                    },
                ])
                ->get((string) $this->feed_url);

            $this->touchFetchSchedule();

            $status = $response->status();

            if ($status === 304) {
                $this->notModified = true;

                return true;
            }

            if ($status < 200 || $status >= 300) {
                Log::warning('Feed fetch non-success', ['url' => $this->feed_url, 'status' => $status]);

                return false;
            }

            $etagHeader = (string) $response->header('ETag');
            $lastModHeader = (string) $response->header('Last-Modified');
            $this->etag = $etagHeader !== '' ? substr($etagHeader, 0, 255) : null;
            $this->last_modified = $lastModHeader !== '' ? substr($lastModHeader, 0, 64) : null;

            // Http::fake() bypasses the sink transfer; fall back to the body.
            $xml = @filesize($tmp) ? @file_get_contents($tmp) : $response->body();

            if ($xml === false) {
                return false;
            }

            return $this->parse($xml, $effectiveUri);
        } catch (ConnectionException $e) {
            Log::warning('Feed fetch failed', ['url' => $this->feed_url, 'error' => $e->getMessage()]);

            return false;
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Parse a raw RSS/Atom document into feed + episode properties.
     *
     * @param  string|null  $effectiveUri  Final URL after HTTP redirects (when known).
     */
    public function parse(string $xmlString, ?string $effectiveUri = null): bool
    {
        $xml = @simplexml_load_string($xmlString);
        if (! $xml instanceof SimpleXMLElement) {
            return false;
        }

        $this->registerNamespaces($xml);

        $items = null;
        $pubdate = null;
        $language = null;

        if (isset($xml->channel)) {
            $channel = $xml->channel;
            $items = $channel->item;
            $this->title = (string) $channel->title;
            $this->url = (string) $channel->link;
            $this->description = (string) $channel->description;
            $pubdate = $channel->lastBuildDate;
            $language = $channel->language;

            $itunesImage = $this->safeXPath($channel, 'itunes:image/@href');
            if (! empty($itunesImage)) {
                $this->image_url = trim((string) $itunesImage[0]);
            } elseif (isset($channel->image->url)) {
                $this->image_url = trim((string) $channel->image->url);
            }

            $this->applyCanonical($channel, $effectiveUri);
        } elseif (isset($xml->entry)) {
            $channel = $xml;
            $items = $xml->entry;
            $this->title = (string) $channel->title;

            foreach ($channel->link as $link) {
                if ((string) $link['rel'] === 'alternate' || ! isset($link['rel'])) {
                    $this->url = (string) $link['href'];
                    break;
                }
            }

            $this->description = (string) ($channel->subtitle ?? $channel->summary ?? '');
            $pubdate = $channel->updated;
            $language = $channel->{'xml:lang'};

            if (isset($channel->logo)) {
                $this->image_url = trim((string) $channel->logo);
            } elseif (isset($channel->icon)) {
                $this->image_url = trim((string) $channel->icon);
            }

            $this->applyCanonical($channel, $effectiveUri);
        } else {
            return false;
        }

        if (! $this->title) {
            return false;
        }

        if ($items) {
            foreach ($items as $item) {
                $episode = $this->parseItem($item);
                if ($episode !== null) {
                    $this->episodes[] = $episode;
                }
            }
        }

        $this->language = $language ? substr((string) $language, 0, 2) : null;

        if ($pubdate) {
            try {
                $this->published_at = new DateTimeImmutable((string) $pubdate);
            } catch (Throwable $e) {
                Log::warning('Invalid feed pubDate', [
                    'pubdate' => (string) $pubdate,
                    'feed' => $this->feed_url,
                    'error' => $e->getMessage(),
                ]);
                $this->published_at = null;
            }
        } else {
            $this->published_at = null;
        }

        return true;
    }

    /**
     * Fetch feed metadata + episodes from the Podcast Index API.
     */
    public function fetchFromPodcastIndex(PodcastIndexClient $client): bool
    {
        $podcast = $client->podcastByFeedUrl((string) $this->feed_url);

        if (! $podcast) {
            return false;
        }

        if (! empty($podcast['url'])) {
            $canonical = Url::normalizeFeed((string) $podcast['url']);
            if ($canonical !== '' && $canonical !== $this->feed_url) {
                $this->aliases[] = $this->feed_url;
                $this->feed_url = $canonical;
            }
        }

        $this->title = ! empty($podcast['title']) ? (string) $podcast['title'] : null;
        $this->url = ! empty($podcast['link']) ? (string) $podcast['link'] : null;
        $this->description = ! empty($podcast['description']) ? (string) $podcast['description'] : null;
        $this->image_url = ! empty($podcast['artwork']) ? (string) $podcast['artwork']
            : (! empty($podcast['image']) ? (string) $podcast['image'] : null);
        $this->language = ! empty($podcast['language']) ? substr((string) $podcast['language'], 0, 2) : null;

        $this->touchFetchSchedule();

        if (! $this->title) {
            return false;
        }

        if (! empty($podcast['lastUpdateTime'])) {
            try {
                $this->published_at = new DateTimeImmutable('@'.(int) $podcast['lastUpdateTime']);
            } catch (Throwable) {
                $this->published_at = null;
            }
        }

        $episodes = $client->episodesByFeedId((int) $podcast['id']);

        foreach ($episodes as $item) {
            $audioUrl = ! empty($item['enclosureUrl']) ? (string) $item['enclosureUrl'] : null;

            if (! $audioUrl) {
                continue;
            }

            $pubdate = null;
            if (! empty($item['datePublished'])) {
                try {
                    $pubdate = new DateTimeImmutable('@'.(int) $item['datePublished']);
                } catch (Throwable) {
                }
            }

            $imageUrl = ! empty($item['image']) ? (string) $item['image']
                : (! empty($item['feedImage']) ? (string) $item['feedImage'] : null);

            $this->episodes[] = (object) [
                'image_url' => $imageUrl,
                'url' => ! empty($item['link']) ? (string) $item['link'] : null,
                'media_url' => $audioUrl,
                'published_at' => $pubdate,
                'title' => ! empty($item['title']) ? (string) $item['title'] : null,
                'description' => ! empty($item['description']) ? (string) $item['description'] : null,
                'duration' => $this->validateDuration(! empty($item['duration']) ? (int) $item['duration'] : null),
            ];
        }

        return true;
    }

    /**
     * Persist feed, aliases, subscription pointers and episodes atomically.
     */
    public function sync(): void
    {
        DB::transaction(function (): void {
            $this->resolveCanonicalViaAlias();
            $merged = $this->mergeIntoCanonical();

            $feedId = $this->upsertFeed();

            $this->recordAliases($feedId);
            $this->repointSubscriptions($feedId);

            if ($this->notModified) {
                if ($merged) {
                    $this->backfillEpisodeActions($feedId);
                }
                $this->episodes = [];

                return;
            }

            $flushed = $this->syncEpisodes($feedId);

            if ($flushed || $merged) {
                $this->backfillEpisodeActions($feedId);
            }

            $this->episodes = [];
        });
    }

    // ---------------------------------------------------------- sync steps

    /**
     * Upserts the feed row and returns its id. A 304 carries no body, so only
     * the fetch schedule is touched — writing metadata here would blank out
     * the stored title/description.
     */
    protected function upsertFeed(): int
    {
        $attributes = [
            'last_fetched_at' => $this->last_fetched_at,
            'next_fetch_at' => $this->next_fetch_at,
        ];

        if (! $this->notModified) {
            $attributes += [
                'image_url' => $this->image_url,
                'url' => $this->url,
                'language' => $this->language,
                'title' => $this->title,
                'description' => $this->description,
                'published_at' => $this->published_at?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                'etag' => $this->etag,
                'last_modified' => $this->last_modified,
            ];
        }

        $feed = Feed::query()->updateOrCreate(['feed_url' => $this->feed_url], $attributes);

        return (int) $feed->id;
    }

    /**
     * If the requested URL is a known alias, swap feed_url to the canonical
     * URL of the aliased feed.
     */
    protected function resolveCanonicalViaAlias(): void
    {
        $aliasFeedId = (int) DB::table('feed_aliases')->where('url', $this->feed_url)->value('feed_id');
        if ($aliasFeedId === 0) {
            return;
        }

        $canonical = DB::table('feeds')->where('id', $aliasFeedId)->value('feed_url');
        if ($canonical === null || $canonical === '' || $canonical === $this->feed_url) {
            return;
        }

        $this->aliases[] = $this->feed_url;
        $this->feed_url = (string) $canonical;
    }

    /**
     * If the feed was reassigned to a new canonical URL during fetch, merge
     * the old row (if any) into the existing canonical row, or rename it
     * in-place when no canonical row exists yet. Returns true when an actual
     * merge happened so the caller can re-link episode_actions afterwards.
     */
    protected function mergeIntoCanonical(): bool
    {
        if ($this->requested_url === null || $this->requested_url === $this->feed_url) {
            return false;
        }

        $originalId = (int) DB::table('feeds')->where('feed_url', $this->requested_url)->value('id');
        if ($originalId === 0) {
            return false;
        }

        $canonicalId = (int) DB::table('feeds')->where('feed_url', $this->feed_url)->value('id');

        if ($canonicalId === 0) {
            DB::table('feeds')->where('id', $originalId)->update(['feed_url' => $this->feed_url]);

            return false;
        }

        if ($canonicalId === $originalId) {
            return false;
        }

        DB::table('subscriptions')->where('feed_id', $originalId)->update(['feed_id' => $canonicalId]);

        // Drop rows that would violate the (feed_id, media_url) unique key
        // after the move, then move the rest.
        DB::table('episodes')->where('feed_id', $originalId)
            ->whereIn('media_url', DB::table('episodes')->select('media_url')->where('feed_id', $canonicalId))
            ->delete();
        DB::table('episodes')->where('feed_id', $originalId)->update(['feed_id' => $canonicalId]);

        DB::table('feed_aliases')->where('feed_id', $originalId)->update(['feed_id' => $canonicalId]);
        DB::table('feeds')->where('id', $originalId)->delete();

        return true;
    }

    protected function recordAliases(int $feedId): void
    {
        if ($feedId <= 0 || $this->aliases === []) {
            return;
        }

        $now = now();
        $seen = [];

        foreach ($this->aliases as $alias) {
            if (! is_string($alias) || $alias === '' || $alias === $this->feed_url || isset($seen[$alias])) {
                continue;
            }
            $seen[$alias] = true;

            DB::table('feed_aliases')->upsert(
                ['url' => $alias, 'feed_id' => $feedId, 'created_at' => $now],
                ['url'],
                ['feed_id']
            );
        }

        $this->aliases = [];
    }

    /**
     * Points subscriptions at the synced feed, matching both the canonical URL
     * and any recorded alias.
     */
    protected function repointSubscriptions(int $feedId): void
    {
        DB::table('subscriptions')->where('url', $this->feed_url)->update(['feed_id' => $feedId]);

        DB::table('subscriptions')
            ->whereIn('url', DB::table('feed_aliases')->select('url')->where('feed_id', $feedId))
            ->update(['feed_id' => $feedId]);
    }

    /**
     * Upserts parsed episodes in chunks on the (feed_id, media_url) unique key.
     * Only episodes newer than the feed's newest stored published_at are
     * written. Returns true when at least one batch was flushed.
     */
    protected function syncEpisodes(int $feedId): bool
    {
        $lastPublished = DB::table('episodes')->where('feed_id', $feedId)->max('published_at');
        $lastPublishedTs = $lastPublished ? strtotime((string) $lastPublished) : 0;

        $updateCols = ['title', 'description', 'url', 'image_url', 'published_at', 'duration', 'updated_at'];
        $chunkSize = max(1, (int) config('sintoniza.feeds.upsert_chunk', 200));
        $now = now()->toDateTimeString();
        $utc = new DateTimeZone('UTC');

        $buffer = [];
        $flushed = false;

        foreach ($this->episodes as $episode) {
            $episode = (array) $episode;

            if (empty($episode['media_url'])) {
                continue;
            }

            $pubdate = $episode['published_at'] ?? null;
            if ($lastPublishedTs > 0 && $pubdate instanceof DateTimeInterface
                && $pubdate->getTimestamp() < $lastPublishedTs) {
                continue;
            }

            $buffer[] = [
                'feed_id' => $feedId,
                'media_url' => $episode['media_url'],
                'title' => $episode['title'] ?? null,
                'description' => $episode['description'] ?? null,
                'url' => $episode['url'] ?? null,
                'image_url' => $episode['image_url'] ?? null,
                'published_at' => $pubdate instanceof DateTimeInterface
                    ? $pubdate->setTimezone($utc)->format('Y-m-d H:i:s')
                    : null,
                'duration' => $this->validateDuration($episode['duration'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($buffer) >= $chunkSize) {
                DB::table('episodes')->upsert($buffer, ['feed_id', 'media_url'], $updateCols);
                $buffer = [];
                $flushed = true;
            }
        }

        if ($buffer !== []) {
            DB::table('episodes')->upsert($buffer, ['feed_id', 'media_url'], $updateCols);
            $flushed = true;
        }

        return $flushed;
    }

    /**
     * Re-link episode_actions.episode_id by joining on media_url.
     */
    protected function backfillEpisodeActions(int $feedId): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                'UPDATE episode_actions ea
                 INNER JOIN episodes e ON e.media_url = ea.url
                 SET ea.episode_id = e.id
                 WHERE e.feed_id = ?',
                [$feedId]
            );

            return;
        }

        // Portable fallback (sqlite test database): loop episodes in chunks.
        DB::table('episodes')->where('feed_id', $feedId)
            ->select(['id', 'media_url'])
            ->orderBy('id')
            ->chunk(500, function ($episodes): void {
                foreach ($episodes as $episode) {
                    DB::table('episode_actions')
                        ->where('url', $episode->media_url)
                        ->update(['episode_id' => $episode->id]);
                }
            });
    }

    // ------------------------------------------------------------- parsing

    protected function parseItem(SimpleXMLElement $item): ?object
    {
        $audioUrl = null;

        if (isset($item->enclosure['url'])) {
            $audioUrl = trim((string) $item->enclosure['url']);
        } elseif (isset($item->link)) {
            foreach ($item->link as $link) {
                if (str_starts_with((string) $link['type'], 'audio/')) {
                    $audioUrl = trim((string) $link['href']);
                    break;
                }
            }
        }

        if (! $audioUrl) {
            return null;
        }

        $title = isset($item->title) ? trim((string) $item->title) : null;

        $content = $item->children(self::NAMESPACES['content']);
        if (isset($item->description)) {
            $description = trim((string) $item->description);
        } elseif (isset($content->encoded)) {
            $description = trim((string) $content->encoded);
        } elseif (isset($item->content)) {
            $description = trim((string) $item->content);
        } else {
            $description = null;
        }

        $link = null;
        if (isset($item->link)) {
            if (isset($item->link['href'])) {
                $link = trim((string) $item->link['href']);
            } else {
                // RSS-style plain text <link>, as opposed to Atom's href.
                $text = trim((string) $item->link);
                if ($text !== '') {
                    $link = $text;
                }
            }
        }

        $pubDate = null;
        if (isset($item->pubDate)) {
            $pubDate = trim((string) $item->pubDate);
        } elseif (isset($item->published)) {
            $pubDate = trim((string) $item->published);
        } elseif (isset($item->updated)) {
            $pubDate = trim((string) $item->updated);
        }

        $duration = null;
        if (isset($item->enclosure['length']) && ctype_digit((string) $item->enclosure['length'])) {
            // Enclosure length is a byte size, so apply the heuristic here.
            // validateDuration() is idempotent and runs again during sync().
            $duration = $this->validateDuration((int) $item->enclosure['length']);
        } else {
            $durationNodes = $this->safeXPath($item, 'itunes:duration');
            if (! empty($durationNodes)) {
                $duration = $this->getDuration((string) $durationNodes[0]);
            }
        }

        $imageUrl = null;
        $itunesImage = $this->safeXPath($item, 'itunes:image/@href');
        if (! empty($itunesImage)) {
            $imageUrl = trim((string) $itunesImage[0]);
        } else {
            // SimpleXML only reaches namespaced elements via children($ns);
            // $item->{'media:content'} silently never matches.
            $media = $item->children(self::NAMESPACES['media']);
            if (isset($media->content)) {
                $imageUrl = trim((string) $media->content->attributes()->url);
            } elseif (isset($media->thumbnail)) {
                $imageUrl = trim((string) $media->thumbnail->attributes()->url);
            }
            if ($imageUrl === '') {
                $imageUrl = null;
            }
        }

        $parsedPubDate = null;
        if ($pubDate) {
            try {
                $parsedPubDate = new DateTimeImmutable($pubDate);
            } catch (Throwable $e) {
                Log::warning('Invalid episode pubDate', [
                    'pubdate' => $pubDate,
                    'feed' => $this->feed_url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return (object) [
            'image_url' => $imageUrl,
            'url' => $link,
            'media_url' => $audioUrl,
            'published_at' => $parsedPubDate,
            'title' => $title,
            'description' => $description,
            'duration' => $duration,
        ];
    }

    /**
     * Determine the canonical feed URL from RSS-native signals and HTTP redirects.
     * Priority: itunes:new-feed-url > atom:link rel="self" > effective URI after redirects.
     * If the resolved canonical differs from the requested feed_url, the old URL is
     * recorded as an alias and $this->feed_url is swapped to the canonical form.
     */
    protected function applyCanonical(SimpleXMLElement $channel, ?string $effectiveUri): void
    {
        $itunesNew = null;
        $nodes = $this->safeXPath($channel, 'itunes:new-feed-url');
        if (! empty($nodes)) {
            $itunesNew = trim((string) $nodes[0]);
        }

        $atomSelf = null;
        $links = $this->safeXPath($channel, 'atom:link');
        foreach ($links as $link) {
            if ((string) $link['rel'] === 'self' && (string) $link['href'] !== '') {
                $atomSelf = trim((string) $link['href']);
                break;
            }
        }

        $candidate = $itunesNew ?: ($atomSelf ?: $effectiveUri);
        if (! $candidate) {
            return;
        }

        $canonical = Url::normalizeFeed($candidate);
        if ($canonical === '' || $canonical === $this->feed_url) {
            return;
        }

        $this->aliases[] = $this->feed_url;
        $this->feed_url = $canonical;
    }

    // ------------------------------------------------------------- helpers

    protected function touchFetchSchedule(): void
    {
        $interval = (int) config('sintoniza.feeds.fetch_interval', 86400);
        $this->last_fetched_at = now();
        $this->next_fetch_at = now()->addSeconds($interval);
    }

    /**
     * Values above 24h are assumed to be an enclosure byte size rather than a
     * duration, and are converted to seconds at 128 kbps.
     */
    protected function validateDuration(mixed $duration): ?int
    {
        if ($duration === null) {
            return null;
        }

        $duration = (int) $duration;

        if ($duration > self::MAX_DURATION) {
            $duration = (int) ($duration / (128 * 1024 / 8));
        }

        if ($duration < self::MIN_DURATION || $duration > self::MAX_DURATION) {
            return null;
        }

        return $duration;
    }

    protected function getDuration(?string $str): ?int
    {
        if (! $str) {
            return null;
        }

        if (str_contains($str, ':')) {
            $parts = explode(':', $str);
            $count = count($parts);

            $duration = match ($count) {
                3 => (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2],
                2 => (int) $parts[0] * 60 + (int) $parts[1],
                default => (int) $parts[0],
            };
        } else {
            $duration = (int) $str;
        }

        return $this->validateDuration($duration);
    }

    protected function registerNamespaces(SimpleXMLElement $xml): void
    {
        foreach (self::NAMESPACES as $prefix => $uri) {
            $xml->registerXPathNamespace($prefix, $uri);
        }
    }

    /** @return array<int, SimpleXMLElement> */
    protected function safeXPath(SimpleXMLElement $xml, string $path): array
    {
        try {
            return $xml->xpath($path) ?: [];
        } catch (Throwable) {
            return [];
        }
    }
}
