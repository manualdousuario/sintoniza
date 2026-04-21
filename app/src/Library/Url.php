<?php

declare(strict_types=1);

namespace Sintoniza\Library;

class Url
{
    public static function normalize(string $url): string
    {
        return rtrim(strtolower(trim($url)), '/');
    }
}
