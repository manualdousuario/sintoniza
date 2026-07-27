<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Podcast Index API client.
 *
 * Auth: X-Auth-Key + X-Auth-Date (unix ts) + Authorization sha1(key.secret.ts).
 */
class PodcastIndexClient
{
    private const DEFAULT_BASE_URI = 'https://api.podcastindex.org/api/1.0';

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $apiSecret = null,
        private readonly ?string $baseUri = null,
    ) {}

    public function isConfigured(): bool
    {
        return $this->key() !== '' && $this->secret() !== '';
    }

    /** @return array<int, array<string, mixed>> */
    public function searchByTerm(string $term, int $max = 20): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $data = $this->request('/search/byterm', ['q' => $term, 'max' => $max]);

        if (! $data || ($data['status'] ?? '') !== 'true') {
            return [];
        }

        return $data['feeds'] ?? [];
    }

    /** @return array<string, mixed>|null */
    public function podcastByFeedUrl(string $url): ?array
    {
        $data = $this->request('/podcasts/byfeedurl', ['url' => $url]);

        if (! $data || ($data['status'] ?? '') !== 'true' || empty($data['feed']['id'])) {
            return null;
        }

        return $data['feed'];
    }

    /** @return array<int, array<string, mixed>> */
    public function episodesByFeedId(int $feedId, int $max = 1000): array
    {
        $data = $this->request('/episodes/byfeedid', ['id' => $feedId, 'max' => $max]);

        if (! $data || ($data['status'] ?? '') !== 'true') {
            return [];
        }

        return $data['items'] ?? [];
    }

    /** @return array<string, mixed>|null */
    private function request(string $endpoint, array $params = []): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::baseUrl($this->base())
                ->withHeaders($this->authHeaders())
                ->withUserAgent('Sintoniza')
                ->timeout(30)
                ->get($endpoint, $params);

            if (! $response->successful()) {
                Log::warning('PodcastIndex request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::warning('PodcastIndex request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** @return array<string, string> */
    private function authHeaders(): array
    {
        $timestamp = (string) time();

        return [
            'X-Auth-Key' => $this->key(),
            'X-Auth-Date' => $timestamp,
            'Authorization' => sha1($this->key().$this->secret().$timestamp),
        ];
    }

    private function key(): string
    {
        return $this->apiKey ?? (string) config('sintoniza.podcastindex.key', '');
    }

    private function secret(): string
    {
        return $this->apiSecret ?? (string) config('sintoniza.podcastindex.secret', '');
    }

    private function base(): string
    {
        return $this->baseUri ?? (string) config('sintoniza.podcastindex.base_uri', self::DEFAULT_BASE_URI);
    }
}
