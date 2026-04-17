<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../bootstrap.php';

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Http\Exception\NotFoundException;
use Sintoniza\Api\GpodderApi;
use Sintoniza\Database\DB;
use Sintoniza\Library\Logger;
use Sintoniza\Session\GPodder;

if (PHP_SAPI === 'cli-server' && file_exists(__DIR__ . $_SERVER['REQUEST_URI']) && !is_dir(__DIR__ . $_SERVER['REQUEST_URI'])) {
	return false;
}

// Fix issues with badly configured web servers
if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
	@list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

$whoops = new \Whoops\Run();
$whoops->prependHandler(
	defined('DEBUG') && DEBUG
		? new \Whoops\Handler\PrettyPageHandler()
		: new \Whoops\Handler\PlainTextHandler()
);
$whoops->register();

$db      = new DB(MYSQL_HOST, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD, MYSQL_PORT);
$logger  = Logger::getInstance();
$container = \Sintoniza\Library\Container::getInstance();
$client  = $container->get(\GuzzleHttp\Client::class);
$session = $container->get(\Josantonius\Session\Session::class);
$api     = new GpodderApi($db, $logger, $client, $session);
$gpodder = new GPodder($db, $session);

// Handle GPodder API requests (exits for API calls, returns for web requests)
try {
	$api->handleRequest();
} catch (\JsonException $e) {
	return;
}

// Set timezone
if ($gpodder->isLogged()) {
	date_default_timezone_set($gpodder->user->timezone);
} else {
	date_default_timezone_set('UTC');
}

// Dispatch web request through league/route
$request  = ServerRequestFactory::fromGlobals()->withAttribute('gpodder', $gpodder);
$router   = buildRouter();

try {
    $response = $router->dispatch($request);
} catch (NotFoundException $e) {
    ob_start();
    html_head(__('messages.page_not_found'));
    echo '<div class="container py-5 text-center"><h1>404</h1><p>' . __('messages.page_not_found') . '.</p><a href="/" class="btn btn-primary">' . __('general.home') . '</a></div>';
    html_foot();
    $response = new HtmlResponse(ob_get_clean(), 404);
}

(new SapiEmitter())->emit($response);
