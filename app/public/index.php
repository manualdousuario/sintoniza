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
$gpodder = new GPodder($db, $logger, $client, $session);

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

// Helper functions used by views (will be removed in Fase 6 — Plates)
function isAdmin(): bool
{
	global $gpodder;
	return $gpodder->user && $gpodder->user->admin === 1;
}

function format_description(?string $str): string
{
	if ($str === null) {
		return '';
	}
	$str = str_replace('</p>', "\n\n", $str);
	$str = preg_replace_callback('!<a[^>]*href=(".*?"|\'.*?\'|\S+)[^>]*>(.*?)</a>!i', function ($match) {
		$url = trim($match[1], '"\'');
		return $url === $match[2] ? $match[1] : '[' . $match[2] . '](' . $url . ')';
	}, $str);
	$str = htmlspecialchars(strip_tags($str));
	$str = preg_replace("!(?:\r?\n){3,}!", "\n\n", $str);
	$str = preg_replace('!\[([^\]]+)\]\(([^\)]+)\)!', '<a href="$2">$1</a>', $str);
	$str = preg_replace(';(?<!")https?://[^<\s]+(?!");', '<a href="$0">$0</a>', $str);
	$str = nl2br($str);
	return $str;
}

// Dispatch web request through league/route
$request  = ServerRequestFactory::fromGlobals()->withAttribute('gpodder', $gpodder);
$router   = buildRouter();

try {
    $response = $router->dispatch($request);
} catch (NotFoundException $e) {
    ob_start();
    html_head('Página não encontrada');
    echo '<div class="container py-5 text-center"><h1>404</h1><p>Página não encontrada.</p><a href="/" class="btn btn-primary">Início</a></div>';
    html_foot();
    $response = new HtmlResponse(ob_get_clean(), 404);
}

(new SapiEmitter())->emit($response);
