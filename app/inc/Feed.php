<?php

class Feed
{
	public ?string $feed_url = null;
	public ?string $image_url = null;
	public ?string $url = null;
	public ?string $language = null;
	public ?string $title = null;
	public ?string $description = null;
	public ?\DateTime $pubdate = null;
	public int $last_fetch;
	protected array $episodes = [];

	protected const NAMESPACES = [
		'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
		'content' => 'http://purl.org/rss/1.0/modules/content/',
		'media' => 'http://search.yahoo.com/mrss/',
		'dc' => 'http://purl.org/dc/elements/1.1/',
		'atom' => 'http://www.w3.org/2005/Atom'
	];

	public function __construct(string $url)
	{
		$this->feed_url = $url;
	}

	protected const MAX_DURATION = 86400;
	protected const MIN_DURATION = 20;
	protected function validateDuration($duration): ?int
    {
        if ($duration === null) {
            return null;
        }

        $duration = (int)$duration;
        if ($duration > self::MAX_DURATION) {
            $duration = (int)($duration / (128 * 1024 / 8));
        }

        if ($duration < self::MIN_DURATION || $duration > self::MAX_DURATION) {
            return null;
        }

        return $duration;
    }

	public function load(\stdClass $data): void
	{
		foreach ($data as $key => $value) {
			if ($key === 'id') {
				continue;
			}
			elseif ($key === 'pubdate' && $value) {
				$this->$key = new \DateTime($value);
			}
			else {
				$this->$key = $value;
			}
		}
	}

	protected function registerNamespaces(\SimpleXMLElement $xml): void
    {
        foreach (self::NAMESPACES as $prefix => $uri) {
            $xml->registerXPathNamespace($prefix, $uri);
        }
    }

	protected function safeXPath(\SimpleXMLElement $xml, string $path): array
    {
        try {
            return $xml->xpath($path) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

	public function sync(Database $db): void
	   {
	       $db->beginTransaction();
	       
	       try {
            // Insert/update feed and get ID in one operation
            $db->upsert('feeds', $this->export(), ['feed_url']);
            $feed_id = $db->firstColumn('SELECT id FROM feeds WHERE feed_url = ?', $this->feed_url);
            
            // Batch update subscriptions
            $db->simple('UPDATE subscriptions SET feed = ? WHERE url = ?', $feed_id, $this->feed_url);

            // Prepare batch episode data
            $episode_data = [];
            foreach ($this->episodes as $episode) {
                $episode = (array) $episode;
                
                // Skip episodes without required media_url
                if (empty($episode['media_url'])) {
                    continue;
                }

                $episode['pubdate'] = $episode['pubdate'] ? $episode['pubdate']->format('Y-m-d H:i:s') : null;
                $episode['feed'] = $feed_id;
                $episode['title'] = $episode['title'] ?? null;
                $episode['description'] = $episode['description'] ?? null;
                $episode['url'] = $episode['url'] ?? null;
                $episode['image_url'] = $episode['image_url'] ?? null;
                $episode['duration'] = $this->validateDuration($episode['duration']);
                
                $episode_data[] = $episode;
            }

            // Only proceed if we have valid episodes
            if (!empty($episode_data)) {
                // Create temporary table with proper UTF8MB4 encoding and column types
                $db->exec('CREATE TEMPORARY TABLE tmp_episodes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    media_url TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                    feed INT NOT NULL,
                    title TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                    description MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                    url TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                    image_url TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                    pubdate DATETIME,
                    duration INT,
                    INDEX (id),
                    INDEX (feed)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

                // Batch insert into temporary table
                foreach ($episode_data as $episode) {
                    $db->simple('INSERT INTO tmp_episodes (media_url, feed, title, description, url, image_url, pubdate, duration) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        $episode['media_url'],
                        $episode['feed'],
                        $episode['title'],
                        $episode['description'],
                        $episode['url'],
                        $episode['image_url'],
                        $episode['pubdate'],
                        $episode['duration']
                    );
                }

                // Perform batch upsert using a JOIN approach
                $db->exec('INSERT INTO episodes (feed, media_url, title, description, url, image_url, pubdate, duration)
                    SELECT tmp.feed, tmp.media_url, tmp.title, tmp.description, tmp.url, tmp.image_url, tmp.pubdate, tmp.duration
                    FROM tmp_episodes tmp
                    ON DUPLICATE KEY UPDATE
                        title = VALUES(title),
                        description = VALUES(description),
                        url = VALUES(url),
                        image_url = VALUES(image_url),
                        pubdate = VALUES(pubdate),
                        duration = VALUES(duration)');

                // Update episode actions using a JOIN approach
                $db->simple('UPDATE episodes_actions ea
                    INNER JOIN episodes e ON e.media_url = ea.url
                    SET ea.episode = e.id
                    WHERE e.feed = ?', $feed_id);

                // Clean up temporary table
                $db->exec('DROP TEMPORARY TABLE IF EXISTS tmp_episodes');
            }

            $db->commit();
        }
        catch (Exception $e) {
            $db->rollBack();
            $db->exec('DROP TEMPORARY TABLE IF EXISTS tmp_episodes');
            throw $e;
        }
    }

	public function fetch(): bool
	{
		if (function_exists('curl_exec')) {
			$ch = curl_init($this->feed_url);
			curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'User-Agent: oPodSync',
				'Accept-Encoding: gzip'
			]);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_ENCODING, ''); // Handle compressed responses automatically

			$body = @curl_exec($ch);

			if (false === $body) {
				$error = curl_error($ch);
			}

			curl_close($ch);
		}
		else {
			$ctx = stream_context_create([
				'http' => [
					'header'          => "User-Agent: oPodSync\r\nAccept-Encoding: gzip",
					'max_redirects'   => 5,
					'follow_location' => true,
					'timeout'         => 30,
					'ignore_errors'   => true,
				],
				'ssl'  => [
					'verify_peer'       => true,
					'verify_peer_name'  => true,
					'allow_self_signed' => true,
					'SNI_enabled'       => true,
				],
			]);

			$body = @file_get_contents($this->feed_url, false, $ctx);

			// Check if response is gzipped
			if ($body && substr($body, 0, 2) === "\x1f\x8b") {
				$body = @gzdecode($body);
			}
		}

		$this->last_fetch = time();

		if (!$body) {
			return false;
		}

		// Remove any UTF-8 BOM if present
		$body = preg_replace("/^(\xef\xbb\xbf|\\x00\\x00\\xfe\\xff|\\xff\\xfe\\x00\\x00|\\xff\\xfe|\\xfe\\xff)/", "", $body);

		$xml = @simplexml_load_string($body);

		if (!$xml) {
			return false;
		}

		// Register all namespaces early
		$this->registerNamespaces($xml);

		// Handle both RSS and Atom feed formats
		if (isset($xml->channel)) {
			// RSS feed
			$channel = $xml->channel;
			$items = $channel->item;
			$this->title = (string)$channel->title;
			$this->url = (string)$channel->link;
			$this->description = (string)$channel->description;
			$pubdate = $channel->lastBuildDate;
			$language = $channel->language;
			
			// Try multiple image sources
			$itunesImage = $this->safeXPath($channel, 'itunes:image/@href');
			if (!empty($itunesImage)) {
				$this->image_url = trim((string)$itunesImage[0]);
			} elseif(isset($channel->image->url)) {
				$this->image_url = trim((string)$channel->image->url);
			}
		} elseif (isset($xml->entry)) {
			// Atom feed
			$channel = $xml;
			$items = $xml->entry;
			$this->title = (string)$channel->title;
			
			// Handle Atom link
			foreach ($channel->link as $link) {
				if ((string)$link['rel'] === 'alternate' || !isset($link['rel'])) {
					$this->url = (string)$link['href'];
					break;
				}
			}
			
			$this->description = (string)($channel->subtitle ?? $channel->summary ?? '');
			$pubdate = $channel->updated;
			$language = $channel->{'xml:lang'};
			
			if(isset($channel->logo)) {
				$this->image_url = trim((string)$channel->logo);
			} elseif(isset($channel->icon)) {
				$this->image_url = trim((string)$channel->icon);
			}
		} else {
			// Unknown feed format
			return false;
		}

		if (!$this->title) {
			return false;
		}

		if ($items) {
			foreach ($items as $item) {
				// For Atom feeds, handle enclosure differently
				$audioUrl = null;
				if (isset($item->enclosure['url'])) {
					$audioUrl = trim((string)$item->enclosure['url']);
				} elseif (isset($item->link)) {
					// Check if link is audio content in Atom feeds
					foreach ($item->link as $link) {
						$type = (string)$link['type'];
						if (strpos($type, 'audio/') === 0) {
							$audioUrl = trim((string)$link['href']);
							break;
						}
					}
				}

				// Skip if no audio URL found
				if (!$audioUrl) {
					continue;
				}

				$title = isset($item->title) ? trim((string)$item->title) : null;
				
				// Handle different description elements
				if(isset($item->description)) {
					$description = trim((string)$item->description);
				} elseif(isset($item->{'content:encoded'})) {
					$description = trim((string)$item->{'content:encoded'});
				} elseif(isset($item->content)) {
					$description = trim((string)$item->content);
				} else {
					$description = null;
				}

				// Handle different link formats
				$link = null;
				if (isset($item->link)) {
					if (is_string($item->link)) {
						$link = trim((string)$item->link);
					} elseif (isset($item->link['href'])) {
						$link = trim((string)$item->link['href']);
					}
				}

				// Handle different date formats
				$pubDate = null;
				if (isset($item->pubDate)) {
					$pubDate = trim((string)$item->pubDate);
				} elseif (isset($item->published)) {
					$pubDate = trim((string)$item->published);
				} elseif (isset($item->updated)) {
					$pubDate = trim((string)$item->updated);
				}

				// Get duration using safe xpath
				$duration = null;
				if (isset($item->enclosure['length']) && ctype_digit((string)$item->enclosure['length'])) {
					$duration = (int)$item->enclosure['length'];
				} else {
					$durationNodes = $this->safeXPath($item, 'itunes:duration');
					if (!empty($durationNodes)) {
						$duration = $this->getDuration((string)$durationNodes[0]);
					}
				}

				// Handle different image formats using safe xpath
				$imageUrl = null;
				$itunesImage = $this->safeXPath($item, 'itunes:image/@href');
				if (!empty($itunesImage)) {
					$imageUrl = trim((string)$itunesImage[0]);
				} elseif(isset($item->{'media:content'}['url'])) {
					$imageUrl = trim((string)$item->{'media:content'}['url']);
				} elseif(isset($item->{'media:thumbnail'}['url'])) {
					$imageUrl = trim((string)$item->{'media:thumbnail'}['url']);
				}

				$this->episodes[] = (object) [
					'image_url'   => $imageUrl,
					'url'         => $link,
					'media_url'   => $audioUrl,
					'pubdate'     => $pubDate ? new \DateTime($pubDate) : null,
					'title'       => $title,
					'description' => $description,
					'duration'    => $duration,
				];
			}
		}

		$this->language = $language ? substr((string)$language, 0, 2) : null;
		$this->pubdate = $pubdate ? new \DateTime((string)$pubdate) : null;

		return true;
	}

	protected function getDuration(?string $str): ?int
	{
		if (!$str) {
			return null;
		}

		if (false !== strpos($str, ':')) {
			$parts = explode(':', $str);
			$duration = ($parts[2] ?? 0) * 3600 + ($parts[1] ?? 0) * 60 + $parts[0] ?? 0;
		}
		else {
			$duration = (int) $str;
		}

		return $this->validateDuration($duration);
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
}
