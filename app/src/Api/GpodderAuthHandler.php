<?php

declare(strict_types=1);

namespace Sintoniza\Api;

use GuzzleHttp\Client;
use Josantonius\Session\Session;
use Monolog\Logger as MonologLogger;
use Sintoniza\Database\DB;
use Sintoniza\Session\GPodder;

class GpodderAuthHandler
{
    public function __construct(
        private GpodderApi $api,
        private DB $db,
        private MonologLogger $logger,
        private Client $client,
        private Session $session
    ) {}

    public function handleAuth(): void
    {
        $this->api->requireMethod('POST');

        strtok($this->api->getPath(), '/');
        $action = strtok('');

        if ($action === 'logout') {
            if (!$this->session->isStarted()) {
                $this->session->start();
            }
            $this->session->clear();
            $this->session->destroy();
            $this->api->error(200, __('messages.logged_out'));
        } elseif ($action !== 'login') {
            $this->api->error(404, __('messages.unknown_login_action') . ' ' . htmlspecialchars($action ?? '', ENT_QUOTES, 'UTF-8'));
        }

        if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
            $this->api->error(401, __('messages.no_username_password'));
        }

        $this->requireAuth();
        $this->api->error(200, __('messages.login_success'));
    }

    public function login(): void
    {
        $login = $_SERVER['PHP_AUTH_USER'];
        [$login] = explode('__', $login, 2);

        $this->api->validatePattern($login, 'username', 'username');

        $user = $this->db->firstRow('SELECT id, password FROM users WHERE name = ?', $login);

        if (!$user) {
            $this->api->error(401, __('messages.invalid_username'));
        }

        if (!password_verify($_SERVER['PHP_AUTH_PW'], $user->password ?? '')) {
            $this->api->error(401, __('messages.invalid_username_password'));
        }

        $this->logger->debug('Usuário conectado', ['user' => $login]);

        if (!$this->session->isStarted()) {
            $this->session->start();
        }
        $this->session->set('user', $user);
    }

    public function requireAuth(?string $username = null): void
    {
        if ($this->api->getUser() !== null) {
            return;
        }

        if ($username && str_contains($username, '__')) {
            $gpodder = new GPodder($this->db, $this->session);
            if (!$gpodder->validateToken($username)) {
                $this->api->error(401, __('messages.invalid_gpodder_token'));
            }

            $this->api->setUser($gpodder->user);
            return;
        }

        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            $this->login();
            $this->api->setUser($this->session->get('user'));
            return;
        }

        if (empty($_COOKIE['sessionid'])) {
            $this->api->error(401, __('messages.session_cookie_required'));
        }

        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        if (!$this->session->has('user')) {
            $this->api->error(401, __('messages.session_expired'));
        }

        $sessionUser = $this->session->get('user');

        if (!$this->db->firstColumn('SELECT 1 FROM users WHERE id = ?', $sessionUser->id)) {
            $this->api->error(401, __('messages.user_not_exists'));
        }

        $this->api->setUser($sessionUser);
        $this->logger->debug('Usuário autenticado via cookie', ['id' => $sessionUser->id]);
    }
}
