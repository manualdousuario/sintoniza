<?php

declare(strict_types=1);

namespace Sintoniza\Library;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class Logger
{
    private static ?MonologLogger $instance = null;

    public static function getInstance(): MonologLogger
    {
        if (self::$instance === null) {
            $logger = new MonologLogger('sintoniza');

            $errorLog = __DIR__ . '/../../logs/error.log';
            $logger->pushHandler(new RotatingFileHandler($errorLog, 30, Level::Error));

            if (defined('DEBUG') && DEBUG) {
                $debugLog = __DIR__ . '/../../logs/debug.log';
                $logger->pushHandler(new StreamHandler($debugLog, Level::Debug));
            }

            self::$instance = $logger;
        }

        return self::$instance;
    }

    private function __construct() {}
}
