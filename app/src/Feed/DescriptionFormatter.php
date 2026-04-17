<?php

declare(strict_types=1);

namespace Sintoniza\Feed;

class DescriptionFormatter
{
    private const UTM_SOURCE = 'sintoniza';

    public static function format(?string $str): string
    {
        if ($str === null) {
            return '';
        }

        $tidy = new \tidy();
        $str = $tidy->repairString($str, [
            'show-body-only'      => true,
            'drop-empty-elements' => true,
            'drop-empty-paras'    => true,
            'wrap'                => 0,
            'output-html'         => true,
            'hide-comments'       => true,
        ], 'utf8');

        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove invisible formatting characters common in podcast feeds
        // (soft hyphen, zero-width marks, bidi controls, word joiner, BOM).
        $str = preg_replace(
            '/[\x{00AD}\x{200B}-\x{200F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{FEFF}]/u',
            '',
            $str
        );
        $str = preg_replace('/\x{00A0}+/u', ' ', $str);
        $str = preg_replace('!(?:\s*<br\s*/?>\s*){2,}!i', "\n\n", $str);

        $str = str_replace('</p>', "\n\n", $str);
        $str = preg_replace_callback('!<a[^>]*href=(".*?"|\'.*?\'|\S+)[^>]*>(.*?)</a>!i', function ($match) {
            $url = trim($match[1], '"\'');
            return $url === $match[2] ? $match[1] : '[' . $match[2] . '](' . $url . ')';
        }, $str);
        $str = htmlspecialchars(strip_tags($str));

        $str = preg_replace_callback('!\[([^\]]+)\]\(([^\)]+)\)!', function ($match) {
            $text = $match[1];
            $url  = $match[2];
            if (!preg_match('!^https?://!i', $url)) {
                return $text;
            }
            return self::anchor(self::withUtm($url), $text);
        }, $str);
        $str = preg_replace_callback(';(?<!")https?://[^<\s]+(?!");', function ($match) {
            return self::anchor(self::withUtm($match[0]), $match[0]);
        }, $str);

        $str = preg_replace("!(?:\r?\n){3,}!", "\n\n", $str);
        $str = nl2br($str);
        $str = preg_replace('!(?:<br\s*/?>\s*){3,}!i', "<br />\n<br />\n", $str);

        return $str;
    }

    private static function withUtm(string $url): string
    {
        if (preg_match('/[?&]utm_source=/i', $url)) {
            return $url;
        }
        $frag = '';
        if (($hashPos = strpos($url, '#')) !== false) {
            $frag = substr($url, $hashPos);
            $url  = substr($url, 0, $hashPos);
        }
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . 'utm_source=' . self::UTM_SOURCE . $frag;
    }

    private static function anchor(string $href, string $text): string
    {
        return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $text . '</a>';
    }
}
