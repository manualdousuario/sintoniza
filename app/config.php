<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Define
define("SINTONIZA_DB_HOST", isset($_ENV['SINTONIZA_DB_HOST']) ? $_ENV['SINTONIZA_DB_HOST'] : 'localhost');
define("SINTONIZA_DB_USER", isset($_ENV['SINTONIZA_DB_USER']) ? $_ENV['SINTONIZA_DB_USER'] : 'root');
define("SINTONIZA_DB_PASS", isset($_ENV['SINTONIZA_DB_PASS']) ? $_ENV['SINTONIZA_DB_PASS'] : '');
define("SINTONIZA_DB_PORT", isset($_ENV['SINTONIZA_DB_PORT']) ? $_ENV['SINTONIZA_DB_PORT'] : 3306);
define("SINTONIZA_DB_NAME", isset($_ENV['SINTONIZA_DB_NAME']) ? $_ENV['SINTONIZA_DB_NAME'] : 'sintoniza');
define("BASE_URL", isset($_ENV['BASE_URL']) ? $_ENV['BASE_URL'] : '');
define("TITLE", isset($_ENV['TITLE']) ? $_ENV['TITLE'] : 'Sintoniza');
define("ENABLE_SUBSCRIPTIONS", isset($_ENV['ENABLE_SUBSCRIPTIONS'])? filter_var($_ENV['ENABLE_SUBSCRIPTIONS'], FILTER_VALIDATE_BOOLEAN) : false);
define("DEBUG", isset($_ENV['DEBUG']) ? __DIR__ . '/logs/debug.log' : false);
define("DISABLE_USER_METADATA_UPDATE", isset($_ENV['DISABLE_USER_METADATA_UPDATE']) ? filter_var($_ENV['DISABLE_USER_METADATA_UPDATE'], FILTER_VALIDATE_BOOLEAN) : false);

// PHPMailer SMTP Configuration
define("SMTP_USER", isset($_ENV['SMTP_USER']) ? $_ENV['SMTP_USER'] : '');
define("SMTP_PASS", isset($_ENV['SMTP_PASS']) ? $_ENV['SMTP_PASS'] : '');
define("SMTP_HOST", isset($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : '');
define("SMTP_FROM", isset($_ENV['SMTP_FROM']) ? $_ENV['SMTP_FROM'] : '');
define("SMTP_NAME", isset($_ENV['SMTP_NAME']) ? $_ENV['SMTP_NAME'] : '');
define("SMTP_PORT", isset($_ENV['SMTP_PORT']) ? $_ENV['SMTP_PORT'] : '587');
define("SMTP_SECURE", isset($_ENV['SMTP_SECURE']) ? $_ENV['SMTP_SECURE'] : 'tls');
define("SMTP_AUTH", isset($_ENV['SMTP_AUTH']) ? filter_var($_ENV['SMTP_AUTH'], FILTER_VALIDATE_BOOLEAN) : true);

// Functions and classes
require_once __DIR__ . '/inc/Errors.php';
require_once __DIR__ . '/inc/DB.php';
require_once __DIR__ . '/inc/API.php';
require_once __DIR__ . '/inc/GPodder.php';
require_once __DIR__ . '/inc/Feed.php';
require_once __DIR__ . '/inc/Language.php';

Language::getInstance();

// Templates
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/footer.php';
