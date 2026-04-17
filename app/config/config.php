<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

define('APP_PATH', dirname(__DIR__));

// Define
define('MYSQL_HOST', $_ENV['MYSQL_HOST'] ?? 'localhost');
define('MYSQL_USER', $_ENV['MYSQL_USER'] ?? 'root');
define('MYSQL_PASSWORD', $_ENV['MYSQL_PASSWORD'] ?? '');
define('MYSQL_PORT', $_ENV['MYSQL_PORT'] ?? 3306);
define('MYSQL_DATABASE', $_ENV['MYSQL_DATABASE'] ?? 'sintoniza');
define('BASE_URL', $_ENV['BASE_URL'] ?? '');
define('TITLE', $_ENV['TITLE'] ?? 'Sintoniza');
define('ENABLE_SUBSCRIPTIONS', isset($_ENV['ENABLE_SUBSCRIPTIONS']) ? filter_var($_ENV['ENABLE_SUBSCRIPTIONS'], FILTER_VALIDATE_BOOLEAN) : false);
define('DEBUG', isset($_ENV['DEBUG']) ? filter_var($_ENV['DEBUG'], FILTER_VALIDATE_BOOLEAN) : false);

// Podcast Index API
define('PODCAST_INDEX_API_KEY', $_ENV['PODCAST_INDEX_API_KEY'] ?? '');
define('PODCAST_INDEX_API_SECRET', $_ENV['PODCAST_INDEX_API_SECRET'] ?? '');
define('PODCAST_INDEX_USE_AS_PRIMARY', isset($_ENV['PODCAST_INDEX_USE_AS_PRIMARY']) ? filter_var($_ENV['PODCAST_INDEX_USE_AS_PRIMARY'], FILTER_VALIDATE_BOOLEAN) : false);
define('PODCAST_INDEX_FALLBACK_TO_RSS', isset($_ENV['PODCAST_INDEX_FALLBACK_TO_RSS']) ? filter_var($_ENV['PODCAST_INDEX_FALLBACK_TO_RSS'], FILTER_VALIDATE_BOOLEAN) : true);

// PHPMailer SMTP
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_FROM', $_ENV['SMTP_FROM'] ?? '');
define('SMTP_NAME', $_ENV['SMTP_NAME'] ?? '');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? '587');
define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? 'tls');
define('SMTP_AUTH', isset($_ENV['SMTP_AUTH']) ? filter_var($_ENV['SMTP_AUTH'], FILTER_VALIDATE_BOOLEAN) : true);

\Sintoniza\Library\Language::getInstance();
