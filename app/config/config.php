<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

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

// Templates (define html_head / html_foot functions)
require_once __DIR__ . '/../views/header.php';
require_once __DIR__ . '/../views/footer.php';
