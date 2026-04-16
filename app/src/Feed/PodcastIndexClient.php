<?php

declare(strict_types=1);

namespace Sintoniza\Feed;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Sintoniza\Library\Logger;

class PodcastIndexClient
{
    private const BASE_URL = 'https://api.podcastindex.org/api/1.0';

    public function __construct(
        private Client $client,
        private string $apiKey,
        private string $apiSecret
    ) {}

    public function getPodcastByFeedUrl(string $url): ?array
    {
        $data = $this->request('/podcasts/byfeedurl', ['url' => $url]);

        if (!$data || ($data['status'] ?? '') !== 'true' || empty($data['feed']['id'])) {
            return null;
        }

        return $data['feed'];
    }

    public function getEpisodesByFeedId(int $id, int $max = 1000): array
    {
        $data = $this->request('/episodes/byfeedid', ['id' => $id, 'max' => $max]);

        if (!$data || ($data['status'] ?? '') !== 'true') {
            return [];
        }

        return $data['items'] ?? [];
    }

    private function request(string $endpoint, array $params = []): ?array
    {
        try {
            $response = $this->client->get(self::BASE_URL . $endpoint, [
                'headers' => $this->buildHeaders(),
                'query'   => $params,
            ]);

            return json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            Logger::getInstance()->warning('PodcastIndex request failed', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildHeaders(): array
    {
        $timestamp = (string) time();
        $hash      = sha1($this->apiKey . $this->apiSecret . $timestamp);

        return [
            'X-Auth-Key'    => $this->apiKey,
            'X-Auth-Date'   => $timestamp,
            'Authorization' => $hash,
            'User-Agent'    => 'Sintoniza/1.0',
        ];
    }
}
