<?php

declare(strict_types=1);

use Sintoniza\Library\Language;

if (!function_exists('__')) {
    function __(string $key): string
    {
        return Language::getInstance()->get($key);
    }
}
