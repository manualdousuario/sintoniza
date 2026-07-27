<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use League\Uri\Contracts\UriException;
use League\Uri\Uri;

class Gpodder
{
    public const VALIDATION_PATTERNS = [
        'deviceid' => '/^[\w.-]+$/',
        'url' => '!^https?://[^/]+!',
        'username' => '/^[a-zA-Z0-9_-]+$/',
        'timestamp' => '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,3})?(?:Z|[+-]\d{2}:?\d{2})?$/',
    ];

    /**
     * The protocol's {"code": N, "message": "..."} envelope, used for real
     * errors AND for 200 acknowledgements.
     */
    public static function error(int $code, string $message): JsonResponse
    {
        return response()->json(
            ['code' => $code, 'message' => self::sanitize($message)],
            $code,
            [],
            JSON_PRETTY_PRINT
        );
    }

    public static function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /** A failing 'url' pattern is reported by the caller, not aborted here. */
    public static function validatePattern(string $input, string $pattern, string $fieldName): ?JsonResponse
    {
        if (! isset(self::VALIDATION_PATTERNS[$pattern])) {
            throw new \InvalidArgumentException('Invalid validation pattern specified');
        }

        if (! preg_match(self::VALIDATION_PATTERNS[$pattern], $input)) {
            if ($pattern !== 'url') {
                return self::error(400, sprintf(__('sintoniza.errors.invalid_%s'), $fieldName));
            }
        }

        return null;
    }

    public static function validateUrl(string $url): bool
    {
        try {
            $parsed = Uri::new($url);

            return in_array($parsed->getScheme(), ['http', 'https'], true) && (bool) $parsed->getHost();
        } catch (UriException) {
            return false;
        }
    }

    /**
     * Merges each row's JSON "data" blob into the row itself, with the row's
     * own attributes winning, then drops the blob.
     *
     * @param  iterable<object>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function mergeDataRows(iterable $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $row = (array) $row;

            if (isset($row['data']) && is_string($row['data'])) {
                try {
                    $jsonData = json_decode($row['data'], true, 512, JSON_THROW_ON_ERROR);
                    $row = array_merge($jsonData, $row);
                    unset($row['data']);
                } catch (\JsonException) {
                    continue;
                }
            } else {
                unset($row['data']);
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $urls
     */
    public static function opml(array $urls): Response
    {
        $out = '<?xml version="1.0" encoding="utf-8"?>';
        $out .= PHP_EOL.'<opml version="1.0"><head><title>My Feeds</title></head><body>';

        foreach ($urls as $url) {
            $out .= PHP_EOL.sprintf('<outline type="rss" xmlUrl="%s" />', htmlspecialchars($url, ENT_XML1));
        }

        $out .= PHP_EOL.'</body></opml>';

        return response($out, 200, ['Content-Type' => 'text/x-opml; charset=utf-8']);
    }

    public static function url(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/').($path !== '' ? '/'.ltrim(self::sanitize($path), '/') : '');
    }
}
