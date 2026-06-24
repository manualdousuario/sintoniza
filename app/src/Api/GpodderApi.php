<?php

declare(strict_types=1);

namespace Sintoniza\Api;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Josantonius\Session\Session;
use JsonException;
use League\Uri\Uri;
use Monolog\Logger as MonologLogger;
use Sintoniza\Database\DB;
use stdClass;

class GpodderApi
{
    protected ?string $method  = null;
    protected ?stdClass $user  = null;
    protected ?string $section = null;
    public ?string $url        = null;
    public ?string $base_url   = null;
    public ?string $base_path  = null;
    protected ?string $path    = null;
    protected ?string $format  = null;
    protected DB $db;
    protected MonologLogger $logger;
    protected Client $client;
    protected Session $session;

    protected const VALIDATION_PATTERNS = [
        'deviceid'  => '/^[\w.-]+$/',
        'url'       => '!^https?://[^/]+!',
        'username'  => '/^[a-zA-Z0-9_-]+$/',
        'timestamp' => '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,3})?(?:Z|[+-]\d{2}:?\d{2})?$/',
    ];

    private GpodderAuthHandler $authHandler;
    private GpodderDevicesHandler $devicesHandler;
    private GpodderSubscriptionsHandler $subscriptionsHandler;
    private GpodderEpisodesHandler $episodesHandler;

    public function __construct(DB $db, MonologLogger $logger, Client $client, Session $session)
    {
        $this->db      = $db;
        $this->logger  = $logger;
        $this->client  = $client;
        $this->session = $session;
        $this->session->setName('sessionid');

        $url = defined('BASE_URL') ? BASE_URL : null;
        $url ??= getenv('BASE_URL', true) ?: null;

        if (!$url) {
            if (!isset($_SERVER['SERVER_PORT'], $_SERVER['SERVER_NAME'], $_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT'])) {
                echo __('messages.auto_url_error') . "\n";
                exit(1);
            }

            $url = 'http';

            if (!empty($_SERVER['HTTPS']) || $_SERVER['SERVER_PORT'] === 443) {
                $url .= 's';
            }

            $url .= '://' . $_SERVER['SERVER_NAME'];

            if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
                $url .= ':' . $_SERVER['SERVER_PORT'];
            }

            $path = substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen($_SERVER['DOCUMENT_ROOT']));
            $path = trim($path, '/');
            $url .= $path ? '/' . $path . '/' : '/';
        }

        $this->base_path = Uri::new($url)->getPath();
        $this->base_url  = $url;

        $this->authHandler          = new GpodderAuthHandler($this, $db, $logger, $client, $session);
        $this->devicesHandler       = new GpodderDevicesHandler($this, $db);
        $this->subscriptionsHandler = new GpodderSubscriptionsHandler($this, $db, $logger, new \Sintoniza\Service\FeedIndexer($db));
        $this->episodesHandler      = new GpodderEpisodesHandler($this, $db, $logger);
    }

    // Getters / setters for handler access to protected state

    public function getMethod(): ?string { return $this->method; }
    public function getUser(): ?stdClass { return $this->user; }
    public function setUser(?stdClass $user): void { $this->user = $user; }
    public function getPath(): ?string { return $this->path; }
    public function getFormat(): ?string { return $this->format; }

    public function validatePattern(string $input, string $pattern, string $fieldName): void
    {
        if (!isset(self::VALIDATION_PATTERNS[$pattern])) {
            throw new InvalidArgumentException("Invalid validation pattern specified");
        }

        if (!preg_match(self::VALIDATION_PATTERNS[$pattern], $input)) {
            $this->logger->warning('Validation error', ['pattern' => $pattern, 'field' => $fieldName, 'value' => $input]);

            if ($pattern !== 'url') {
                $this->error(400, sprintf(__('errors.invalid_%s'), $fieldName));
            }
        }
    }

    public function sanitizeString(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    public function url(string $path = ''): string
    {
        return $this->base_url . $this->sanitizeString($path);
    }

    public function debug(string $message, mixed ...$params): void
    {
        if (!defined('DEBUG') || !DEBUG) {
            return;
        }

        $this->logger->debug(vsprintf($message, $params));
    }

    public function queryWithData(string $sql, mixed ...$params): array
    {
        if (empty($sql)) {
            throw new InvalidArgumentException("SQL query cannot be empty");
        }

        $out = [];

        foreach ($this->db->iterate($sql, ...$params) as $row) {
            if (isset($row->data) && is_string($row->data)) {
                try {
                    $jsonData = json_decode($row->data, true, 512, JSON_THROW_ON_ERROR);
                    $row      = (object) array_merge($jsonData, (array) $row);
                    unset($row->data);
                } catch (JsonException $e) {
                    $this->debug('JSON decode error: %s', $e->getMessage());
                    continue;
                }
            }
            $out[] = (array) $row;
        }

        return $out;
    }

    public function error(int $code, string $message): void
    {
        $this->debug('RETURN: %d - %s', $code, $message);
        http_response_code($code);
        header('Content-Type: application/json', true);
        echo json_encode(['code' => $code, 'message' => $this->sanitizeString($message)], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        exit;
    }

    public function requireMethod(string $method): void
    {
        if ($method !== $this->method) {
            $this->error(405, 'Invalid HTTP method: ' . $this->sanitizeString($this->method ?? ''));
        }
    }

    public function validateURL(string $url): bool
    {
        try {
            $parsed = Uri::new($url);
            if (!in_array($parsed->getScheme(), ['http', 'https']) || !$parsed->getHost()) {
                $this->logger->warning('URL validation error', ['url' => $url]);
                return false;
            }
            return true;
        } catch (\League\Uri\Contracts\UriException $e) {
            $this->logger->warning('URL parse error', ['url' => $url, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getDeviceID(string $deviceid, int $user_id): mixed
    {
        $this->validatePattern($deviceid, 'deviceid', 'device_id');
        $this->debug('Procurando ID do dispositivo para deviceid: %s e usuário: %d', $deviceid, $user_id);
        $device_id = $this->db->firstColumn('SELECT id FROM devices WHERE deviceid = ? AND user = ?', $deviceid, $user_id);
        $this->debug('ID do dispositivo encontrado: %s', $device_id ?? 'null');
        return $device_id;
    }

    public function getInput(): mixed
    {
        if ($this->format === 'txt') {
            return array_filter(file('php://input'), 'trim');
        }

        $input = file_get_contents('php://input');

        if (empty($input)) {
            return null;
        }

        try {
            return json_decode($input, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error(400, __('messages.invalid_json'));
            return null;
        }
    }

    public function route(): mixed
    {
        return match ($this->section) {
            'tag', 'tags', 'data', 'toplist', 'suggestions', 'favorites' => [],
            'devices'       => $this->devicesHandler->handle(),
            'updates'       => $this->error(501, __('messages.not_implemented')),
            'subscriptions' => $this->subscriptionsHandler->handle(),
            'episodes'      => $this->episodesHandler->handle(),
            'settings', 'lists', 'sync-device' => $this->error(503, __('messages.not_implemented')),
            default         => null,
        };
    }

    public function handleNextCloud(): ?array
    {
        if ($this->url === 'index.php/login/v2') {
            $this->requireMethod('POST');
            $id = bin2hex(random_bytes(16));

            return [
                'poll'  => ['token' => $id, 'endpoint' => $this->url('index.php/login/v2/poll')],
                'login' => $this->url('login?token=' . $id),
            ];
        }

        if ($this->url === 'index.php/login/v2/poll') {
            $this->requireMethod('POST');

            if (empty($_POST['token']) || !ctype_alnum($_POST['token'])) {
                $this->error(400, __('messages.invalid_gpodder_token'));
            }

            $this->session->setId($_POST['token']);
            if (!$this->session->isStarted()) {
                $this->session->start();
            }

            if (!$this->session->has('user') || !$this->session->has('app_password')) {
                $this->error(404, __('messages.session_expired'));
            }

            return [
                'server'      => $this->url(),
                'loginName'   => $this->session->get('user')->name,
                'appPassword' => $this->session->get('app_password'),
            ];
        }

        $nextcloud_path = 'index.php/apps/gpoddersync/';

        if (!str_starts_with($this->url, $nextcloud_path)) {
            return null;
        }

        if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
            $this->error(401, __('messages.no_username_password'));
        }

        $this->debug('Compatibilidade com Nextcloud: %s / %s', $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

        $user = $this->db->firstRow('SELECT id, password FROM users WHERE name = ?', $_SERVER['PHP_AUTH_USER']);

        if (!$user) {
            $this->error(401, __('messages.invalid_username'));
        }

        $token        = strtok($_SERVER['PHP_AUTH_PW'], ':');
        $password     = strtok('');
        $app_password = sha1($user->password . $token);

        if ($app_password !== $password) {
            $this->error(401, __('messages.invalid_username_password'));
        }

        $this->user = $user;

        $path = substr($this->url, strlen($nextcloud_path));

        if ($path === 'subscriptions' || $path === 'subscription_change/create') {
            $this->url = 'api/2/subscriptions/current/default.json';
        } elseif ($path === 'episode_action' || $path === 'episode_action/create') {
            $this->url = 'api/2/episodes/current.json';
        } else {
            $this->error(404, __('messages.nextcloud_undefined_endpoint'));
        }

        return null;
    }

    public function handleRequest(): void
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? null;
        $url          = '/' . trim($_SERVER['REQUEST_URI'] ?? '', '/');
        $url          = substr($url, strlen($this->base_path));
        $this->url    = strtok($url, '?') ?: '';

        $this->debug('Recebi uma solicitação %s em %s', $this->method, $this->url);

        $return = $this->handleNextCloud();

        if ($return) {
            echo json_encode($return, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            exit;
        }

        if (!preg_match('!^(suggestions|subscriptions|toplist|api/2/(auth|subscriptions|devices|updates|episodes|favorites|settings|lists|sync-devices|tags?|data))/!', $this->url, $match)) {
            return;
        }

        $this->section = $match[2] ?? $match[1];
        $this->path    = substr($this->url, strlen($match[0]));
        $username      = null;

        if (preg_match('/\.(json|opml|txt|jsonp|xml)$/', $this->url, $match)) {
            $this->format = $match[1];
            $this->path   = substr($this->path, 0, -strlen($match[0]));
        }

        if (!in_array($this->format, ['json', 'opml', 'txt'])) {
            $this->error(501, __('messages.output_format_not_implemented'));
        }

        if (preg_match('!(\w+__\w{10})!i', $this->path, $match)) {
            $username = $match[1];
            $this->validatePattern($username, 'username', 'username');
        }

        if ($this->section === 'auth') {
            $this->authHandler->handleAuth();
            return;
        }

        $this->authHandler->requireAuth($username);

        $return = $this->route();

        $this->debug("RETURN:\n%s", json_encode($return, JSON_PRETTY_PRINT));

        if ($this->format === 'opml') {
            if ($this->section !== 'subscriptions') {
                $this->error(501, __('messages.output_format_not_implemented'));
            }

            header('Content-Type: text/x-opml; charset=utf-8');
            echo $this->opml($return);
        } else {
            header('Content-Type: application/json');

            if ($return !== null) {
                echo json_encode($return, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            }
        }

        exit;
    }

    public function opml(array $data): string
    {
        $out  = '<?xml version="1.0" encoding="utf-8"?>';
        $out .= PHP_EOL . '<opml version="1.0"><head><title>My Feeds</title></head><body>';

        foreach ($data as $row) {
            $out .= PHP_EOL . sprintf('<outline type="rss" xmlUrl="%s" />', htmlspecialchars($row ?? '', ENT_XML1));
        }

        $out .= PHP_EOL . '</body></opml>';
        return $out;
    }
}
