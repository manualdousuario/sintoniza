<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Driver
{
    public static function isMySql(?string $connection = null): bool
    {
        return in_array(DB::connection($connection)->getDriverName(), ['mysql', 'mariadb'], true);
    }
}
