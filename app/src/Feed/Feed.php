<?php

declare(strict_types=1);

namespace Sintoniza\Feed;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Sintoniza\Database\DB;
use Sintoniza\Library\Logger;
use Sintoniza\Library\Url;

class Feed
{
    public ?string $feed_url      = null;
    public ?string $image_url     = null;
    public ?string $url           = null;
    public ?string $language      = null;
    public ?string $title         = null;
    public ?string $description   = null;
    public ?DateTime $pubdate     = null;
    public int $last_fetch        = 0;
    public int $next_fetch_at     = 0;
    public ?string $etag          = null;
    public ?string $last_modified = null;
    public int $fetch_failures    = 0;
    public int $active            = 1;

    public bool $notModified = false;

    protected array $episodes = [];

    /** URL as originally requested (before any canonical rewrite). */
    protected ?string $requested_url = null;

    /** URLs that should alias to this feed after sync(). */
    protected array $aliases = [];

    protected const FETCH_INTERVAL = 86400;

    protected const NAMESPACES = [
        'itunes'  => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
        'content' => 'http://purl.org/rss/1.0/modules/content/',
        'media'   => 'http://search.yahoo.com/mrss/',
        'dc'      => 'http://purl.org/dc/elements/1.1/',
        'atom'    => 'http://www.w3.org/2005/Atom',
    ];

    protected const MAX_DURATION = 86400;
    protected const MIN_DURATION = 20;

    public function __construct(string $url)
    {
        $this->feed_url      = Url::normalizeFeed($url);
        $this->requested_url = $this->feed_url;
    }

    public function load(\stdClass $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'id') {
                continue;
            }

            if ($key === 'pubdate' && $value) {
                $this->$key = new DateTime($value);
            } elseif (in_array($key, ['last_fetch', 'next_fetch_at', 'fetch_failures', 'active'], true)) {
                $this->$key = (int) $value;
            } else {
                $this->$key = $value;
            }
        }
    }

    public function fetch(Client $client): bool
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
            $response = $client->get($this->feed_url, [
                'sink'        => $tmp,
                'headers'     => $headers,
                'http_errors' => false,
                'on_stats'    => function (TransferStats $stats) use (&$effectiveUri) {
                    $effectiveUri = (string) $stats->getEffectiveUri();
                },
            ]);
            $this->last_fetch    = time();
            $this->next_fetch_at = $this->last_fetch + self::FETCH_INTERVAL;

            $status = $response->getStatusCode();

            if ($status === 304) {
                $this->notModified = true;
                @unlink($tmp);
                return true;
            }

            if ($status < 200 || $status >= 300) {
                Logger::getInstance()->warning('Feed fetch non-success', ['url' => $this->feed_url, 'status' => $status]);
                @unlink($tmp);
                return false;
            }

            $etagHeader     = $response->getHeaderLine('ETag');
            $lastModHeader  = $response->getHeaderLine('Last-Modified');
            $this->etag          = $etagHeader !== '' ? substr($etagHeader, 0, 255) : null;
            $this->last_modified = $lastModHeader !== '' ? substr($lastModHeader, 0, 64) : null;
        } catch (RequestException $e) {
            Logger::getInstance()->warning('Feed fetch failed', ['url' => $this->feed_url, 'error' => $e->getMessage()]);
            @unlink($tmp);
            return false;
        }

        if (!filesize($tmp)) {
            @unlink($tmp);
            return false;
        }

        $xml = @simplexml_load_file($tmp);
        @unlink($tmp);

        if (!$xml) {
            return false;
        }

        $this->registerNamespaces($xml);

        if (isset($xml->channel)) {
            $channel  = $xml->channel;
            $items    = $channel->item;
            $this->title       = (string) $channel->title;
            $this->url         = (string) $channel->link;
            $this->description = (string) $channel->description;
            $pubdate  = $channel->lastBuildDate;
            $language = $channel->language;

            $itunesImage = $this->safeXPath($channel, 'itunes:image/@href');
            if (!empty($itunesImage)) {
                $this->image_url = trim((string) $itunesImage[0]);
            } elseif (isset($channel->image->url)) {
                $this->image_url = trim((string) $channel->image->url);
            }

            $this->applyCanonical($channel, $effectiveUri);
        } elseif (isset($xml->entry)) {
            $channel  = $xml;
            $items    = $xml->entry;
            $this->title = (string) $channel->title;

            foreach ($channel->link as $link) {
                if ((string) $link['rel'] === 'alternate' || !isset($link['rel'])) {
                    $this->url = (string) $link['href'];
                    break;
                }
            }

            $this->description = (string) ($channel->subtitle ?? $channel->summary ?? '');
            $pubdate  = $channel->updated;
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

        if (!$this->title) {
            return false;
        }

        if ($items) {
            foreach ($items as $item) {
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

                if (!$audioUrl) {
                    continue;
                }

                $title = isset($item->title) ? trim((string) $item->title) : null;

                if (isset($item->description)) {
                    $description = trim((string) $item->description);
                } elseif (isset($item->{'content:encoded'})) {
                    $description = trim((string) $item->{'content:encoded'});
                } elseif (isset($item->content)) {
                    $description = trim((string) $item->content);
                } else {
                    $description = null;
                }

                $link = null;
                if (isset($item->link)) {
                    if (is_string($item->link)) {
                        $link = trim((string) $item->link);
                    } elseif (isset($item->link['href'])) {
                        $link = trim((string) $item->link['href']);
                    }
                }

                $pubDate = null;
                if (isset($item->pubDate))    $pubDate = trim((string) $item->pubDate);
                elseif (isset($item->published)) $pubDate = trim((string) $item->published);
                elseif (isset($item->updated))   $pubDate = trim((string) $item->updated);

                $duration = null;
                if (isset($item->enclosure['length']) && ctype_digit((string) $item->enclosure['length'])) {
                    $duration = (int) $item->enclosure['length'];
                } else {
                    $durationNodes = $this->safeXPath($item, 'itunes:duration');
                    if (!empty($durationNodes)) {
                        $duration = $this->getDuration((string) $durationNodes[0]);
                    }
                }

                $imageUrl    = null;
                $itunesImage = $this->safeXPath($item, 'itunes:image/@href');
                if (!empty($itunesImage)) {
                    $imageUrl = trim((string) $itunesImage[0]);
                } elseif (isset($item->{'media:content'}['url'])) {
                    $imageUrl = trim((string) $item->{'media:content'}['url']);
                } elseif (isset($item->{'media:thumbnail'}['url'])) {
                    $imageUrl = trim((string) $item->{'media:thumbnail'}['url']);
                }

                $parsedPubDate = null;
                if ($pubDate) {
                    try {
                        $parsedPubDate = new DateTime($pubDate);
                    } catch (Exception $e) {
                        Logger::getInstance()->warning('Invalid episode pubDate', ['pubdate' => $pubDate, 'feed' => $this->feed_url, 'error' => $e->getMessage()]);
                    }
                }

                $this->episodes[] = (object) [
                    'image_url'   => $imageUrl,
                    'url'         => $link,
                    'media_url'   => $audioUrl,
                    'pubdate'     => $parsedPubDate,
                    'title'       => $title,
                    'description' => $description,
                    'duration'    => $duration,
                ];
            }
        }

        $this->language = $language ? substr((string) $language, 0, 2) : null;

        if ($pubdate) {
            try {
                $this->pubdate = new DateTime((string) $pubdate);
            } catch (Exception $e) {
                Logger::getInstance()->warning('Invalid feed pubDate', ['pubdate' => $pubdate, 'feed' => $this->feed_url, 'error' => $e->getMessage()]);
                $this->pubdate = null;
            }
        } else {
            $this->pubdate = null;
        }

        return true;
    }

    public function fetchFromPodcastIndex(PodcastIndexClient $client): bool
    {
        $podcast = $client->getPodcastByFeedUrl($this->feed_url);

        if (!$podcast) {
            return false;
        }

        if (!empty($podcast['url'])) {
            $canonical = Url::normalizeFeed((string) $podcast['url']);
            if ($canonical !== '' && $canonical !== $this->feed_url) {
                $this->aliases[] = $this->feed_url;
                $this->feed_url  = $canonical;
            }
        }

        $this->title         = !empty($podcast['title']) ? (string) $podcast['title'] : null;
        $this->url           = !empty($podcast['link']) ? (string) $podcast['link'] : null;
        $this->description   = !empty($podcast['description']) ? (string) $podcast['description'] : null;
        $this->image_url     = !empty($podcast['artwork']) ? (string) $podcast['artwork']
                             : (!empty($podcast['image']) ? (string) $podcast['image'] : null);
        $this->language      = !empty($podcast['language']) ? substr((string) $podcast['language'], 0, 2) : null;
        $this->last_fetch    = time();
        $this->next_fetch_at = $this->last_fetch + self::FETCH_INTERVAL;

        if (!$this->title) {
            return false;
        }

        if (!empty($podcast['lastUpdateTime'])) {
            try {
                $this->pubdate = new DateTime('@' . (int) $podcast['lastUpdateTime']);
            } catch (Exception $e) {
                $this->pubdate = null;
            }
        }

        $episodes = $client->getEpisodesByFeedId((int) $podcast['id']);

        foreach ($episodes as $item) {
            $audioUrl = !empty($item['enclosureUrl']) ? (string) $item['enclosureUrl'] : null;

            if (!$audioUrl) {
                continue;
            }

            $pubdate = null;
            if (!empty($item['datePublished'])) {
                try {
                    $pubdate = new DateTime('@' . (int) $item['datePublished']);
                } catch (Exception $e) {}
            }

            $imageUrl = !empty($item['image']) ? (string) $item['image']
                      : (!empty($item['feedImage']) ? (string) $item['feedImage'] : null);

            $this->episodes[] = (object) [
                'image_url'   => $imageUrl,
                'url'         => !empty($item['link']) ? (string) $item['link'] : null,
                'media_url'   => $audioUrl,
                'pubdate'     => $pubdate,
                'title'       => !empty($item['title']) ? (string) $item['title'] : null,
                'description' => !empty($item['description']) ? (string) $item['description'] : null,
                'duration'    => $this->validateDuration(!empty($item['duration']) ? (int) $item['duration'] : null),
            ];
        }

        return true;
    }

    public function sync(DB $db): void
    {
        $db->beginTransaction();

        try {
            $this->resolveCanonicalViaAlias($db);
            $merged = $this->mergeIntoCanonical($db);

            $db->upsert('feeds', $this->export(), ['feed_url']);
            $feed_id = (int) $db->firstColumn('SELECT id FROM feeds WHERE feed_url = ?', $this->feed_url);

            $this->recordAliases($db, $feed_id);

            $db->simple('UPDATE subscriptions SET feed = ? WHERE url = ?', $feed_id, $this->feed_url);
            $db->simple(
                'UPDATE subscriptions s
                    INNER JOIN feed_aliases a ON a.url = s.url
                    SET s.feed = a.feed_id
                    WHERE a.feed_id = ?',
                $feed_id
            );

            if ($this->notModified) {
                if ($merged) {
                    $db->simple(
                        'UPDATE episodes_actions ea
                         INNER JOIN episodes e ON e.media_url = ea.url
                         SET ea.episode = e.id
                         WHERE e.feed = ?',
                        $feed_id
                    );
                }
                $this->episodes = [];
                $db->commit();
                return;
            }

            $lastPubdateStr = (string) $db->firstColumn(
                'SELECT MAX(pubdate) FROM episodes WHERE feed = ?',
                $feed_id
            );
            $lastPubdateTs = $lastPubdateStr ? strtotime($lastPubdateStr) : 0;

            $updateCols = ['title', 'description', 'url', 'image_url', 'pubdate', 'duration'];
            $buffer     = [];
            $flushed    = false;

            foreach ($this->episodes as $episode) {
                $episode = (array) $episode;

                if (empty($episode['media_url'])) {
                    continue;
                }

                $pubdateObj = $episode['pubdate'] ?? null;
                if ($lastPubdateTs > 0 && $pubdateObj instanceof DateTime
                    && $pubdateObj->getTimestamp() <= $lastPubdateTs) {
                    continue;
                }

                $buffer[] = [
                    'feed'        => $feed_id,
                    'media_url'   => $episode['media_url'],
                    'title'       => $episode['title'] ?? null,
                    'description' => $episode['description'] ?? null,
                    'url'         => $episode['url'] ?? null,
                    'image_url'   => $episode['image_url'] ?? null,
                    'pubdate'     => $pubdateObj ? $pubdateObj->format('Y-m-d H:i:s') : null,
                    'duration'    => $this->validateDuration($episode['duration']),
                ];

                if (count($buffer) >= 200) {
                    $db->bulkUpsert('episodes', $buffer, $updateCols);
                    $buffer  = [];
                    $flushed = true;
                }
            }

            if (!empty($buffer)) {
                $db->bulkUpsert('episodes', $buffer, $updateCols);
                $flushed = true;
                $buffer  = [];
            }

            if ($flushed || $merged) {
                $db->simple(
                    'UPDATE episodes_actions ea
                     INNER JOIN episodes e ON e.media_url = ea.url
                     SET ea.episode = e.id
                     WHERE e.feed = ?',
                    $feed_id
                );
            }

            $this->episodes = [];

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function export(): array
    {
        $out = get_object_vars($this);
        $out['pubdate'] = $out['pubdate'] ? $out['pubdate']->format('Y-m-d H:i:s \U\T\C') : null;
        unset($out['episodes']);
        return $out;
    }

    public function listEpisodes(): array
    {
        return $this->episodes;
    }

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
        if (!$str) {
            return null;
        }

        if (str_contains($str, ':')) {
            $parts = explode(':', $str);
            $count = count($parts);

            $duration = match ($count) {
                3       => (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2],
                2       => (int) $parts[0] * 60 + (int) $parts[1],
                default => (int) $parts[0],
            };
        } else {
            $duration = (int) $str;
        }

        return $this->validateDuration($duration);
    }

    protected function registerNamespaces(\SimpleXMLElement $xml): void
    {
        foreach (self::NAMESPACES as $prefix => $uri) {
            $xml->registerXPathNamespace($prefix, $uri);
        }
    }

    protected function resolveCanonicalViaAlias(DB $db): void
    {
        $aliasFeedId = (int) $db->firstColumn(
            'SELECT feed_id FROM feed_aliases WHERE url = ?',
            $this->feed_url
        );
        if ($aliasFeedId === 0) {
            return;
        }

        $canonical = $db->firstColumn('SELECT feed_url FROM feeds WHERE id = ?', $aliasFeedId);
        if ($canonical === null || $canonical === '' || $canonical === $this->feed_url) {
            return;
        }

        $this->aliases[] = $this->feed_url;
        $this->feed_url  = (string) $canonical;
    }

    /**
     * If the feed was reassigned to a new canonical URL during fetch, merge the
     * old row (if any) into the existing canonical row, or rename in-place when
     * no canonical row exists yet. Returns true when an actual merge happened
     * so the caller can re-link episodes_actions afterwards.
     */
    protected function mergeIntoCanonical(DB $db): bool
    {
        if ($this->requested_url === null || $this->requested_url === $this->feed_url) {
            return false;
        }

        $originalId = (int) $db->firstColumn('SELECT id FROM feeds WHERE feed_url = ?', $this->requested_url);
        if ($originalId === 0) {
            return false;
        }

        $canonicalId = (int) $db->firstColumn('SELECT id FROM feeds WHERE feed_url = ?', $this->feed_url);

        if ($canonicalId === 0) {
            $db->simple('UPDATE feeds SET feed_url = ? WHERE id = ?', $this->feed_url, $originalId);
            return false;
        }

        if ($canonicalId === $originalId) {
            return false;
        }

        $db->simple('UPDATE subscriptions   SET feed = ? WHERE feed = ?',        $canonicalId, $originalId);
        $db->simple('UPDATE IGNORE episodes SET feed = ? WHERE feed = ?',        $canonicalId, $originalId);
        $db->simple('DELETE FROM episodes   WHERE feed = ?',                     $originalId);
        $db->simple('UPDATE feed_aliases    SET feed_id = ? WHERE feed_id = ?',  $canonicalId, $originalId);
        $db->simple('DELETE FROM feeds      WHERE id = ?',                       $originalId);

        return true;
    }

    protected function recordAliases(DB $db, int $feedId): void
    {
        if ($feedId <= 0 || $this->aliases === []) {
            return;
        }

        $now  = time();
        $seen = [];

        foreach ($this->aliases as $alias) {
            if (!is_string($alias) || $alias === '' || $alias === $this->feed_url || isset($seen[$alias])) {
                continue;
            }
            $seen[$alias] = true;

            $db->simple(
                'INSERT INTO feed_aliases (url, feed_id, created_at) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE feed_id = VALUES(feed_id)',
                $alias,
                $feedId,
                $now
            );
        }

        $this->aliases = [];
    }

    /**
     * Determine the canonical feed URL from RSS-native signals and HTTP redirects.
     * Priority: itunes:new-feed-url > atom:link rel="self" > effective URI after redirects.
     * If the resolved canonical differs from the requested feed_url, the old URL is
     * recorded as an alias and $this->feed_url is swapped to the canonical form.
     */
    protected function applyCanonical(\SimpleXMLElement $channel, ?string $effectiveUri): void
    {
        $itunesNew = null;
        $nodes     = $this->safeXPath($channel, 'itunes:new-feed-url');
        if (!empty($nodes)) {
            $itunesNew = trim((string) $nodes[0]);
        }

        $atomSelf = null;
        $links    = $this->safeXPath($channel, 'atom:link');
        foreach ($links as $link) {
            if ((string) $link['rel'] === 'self' && (string) $link['href'] !== '') {
                $atomSelf = trim((string) $link['href']);
                break;
            }
        }

        $candidate = $itunesNew ?: ($atomSelf ?: $effectiveUri);
        if (!$candidate) {
            return;
        }

        $canonical = Url::normalizeFeed($candidate);
        if ($canonical === '' || $canonical === $this->feed_url) {
            return;
        }

        $this->aliases[] = $this->feed_url;
        $this->feed_url  = $canonical;
    }

    protected function safeXPath(\SimpleXMLElement $xml, string $path): array
    {
        try {
            return $xml->xpath($path) ?: [];
        } catch (Exception) {
            return [];
        }
    }
}
