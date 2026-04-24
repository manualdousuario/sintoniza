<?php

declare(strict_types=1);

namespace Sintoniza\Library;

use League\Uri\Uri;
use Throwable;

class Url
{
    private const TRACKING_PARAMS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_id',
        'fbclid', 'gclid', 'dclid', 'msclkid', 'yclid', 'twclid', 'ttclid', 'igshid',
        'mc_cid', 'mc_eid',
        '_ga', '_gac', '_gl',
        'ref', 'ref_src', 'referrer', 'source',
    ];

    private const DEFAULT_PORTS = [
        'http'  => 80,
        'https' => 443,
    ];

    /**
     * Simple normalization preserved for matching media URLs and legacy callers.
     * Do NOT use for feed URLs — prefer normalizeFeed().
     */
    public static function normalize(string $url): string
    {
        return rtrim(strtolower(trim($url)), '/');
    }

    /**
     * Aggressive normalization intended for podcast feed URLs and subscription
     * URLs (which must join against feed URLs). Produces a canonical form that
     * collapses host/case/tracking variations without collapsing http↔https
     * (that is handled later by following HTTP redirects during fetch).
     */
    public static function normalizeFeed(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        try {
            $uri = Uri::new($url);
        } catch (Throwable) {
            return self::normalize($url);
        }

        $scheme = $uri->getScheme();
        if ($scheme === null || $scheme === '') {
            return self::normalize($url);
        }

        $scheme = strtolower($scheme);

        $host = $uri->getHost();
        if ($host === null || $host === '') {
            return self::normalize($url);
        }

        $host = strtolower($host);
        if (str_starts_with($host, 'www.') && substr_count($host, '.') >= 2) {
            $host = substr($host, 4);
        }

        $port = $uri->getPort();
        if ($port !== null && isset(self::DEFAULT_PORTS[$scheme]) && $port === self::DEFAULT_PORTS[$scheme]) {
            $port = null;
        }

        $path = (string) $uri->getPath();
        if ($path === '') {
            $path = '/';
        }
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $query = self::cleanQuery($uri->getQuery());

        $rebuilt = $scheme . '://' . $host;
        if ($port !== null) {
            $rebuilt .= ':' . $port;
        }
        $rebuilt .= $path;
        if ($query !== '') {
            $rebuilt .= '?' . $query;
        }

        return $rebuilt;
    }

    /**
     * Returns true when two URLs normalize to the same canonical feed form.
     */
    public static function equivalentFeed(string $a, string $b): bool
    {
        return self::normalizeFeed($a) === self::normalizeFeed($b);
    }

    private static function cleanQuery(?string $query): string
    {
        if ($query === null || $query === '') {
            return '';
        }

        $pairs = [];
        foreach (explode('&', $query) as $part) {
            if ($part === '') {
                continue;
            }
            [$k, $v] = array_pad(explode('=', $part, 2), 2, null);
            $k = rawurldecode((string) $k);
            if ($k === '' || in_array(strtolower($k), self::TRACKING_PARAMS, true)) {
                continue;
            }
            $pairs[] = [$k, $v];
        }

        if ($pairs === []) {
            return '';
        }

        usort($pairs, fn(array $x, array $y) => strcmp($x[0], $y[0]));

        $out = [];
        foreach ($pairs as [$k, $v]) {
            $out[] = $v === null ? rawurlencode($k) : rawurlencode($k) . '=' . $v;
        }

        return implode('&', $out);
    }
}
