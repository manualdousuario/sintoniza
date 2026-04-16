<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Josantonius\Session\Session;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Database\DB;
use Sintoniza\Exception\AuthException;
use Sintoniza\Exception\ValidationException;
use Sintoniza\Service\UserService;

class DashboardController
{
    public function __construct(
        private DB $db,
        private UserService $userService,
        private Session $session
    ) {}

    private const SUBS_PER_PAGE = 20;

    public function index(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        $page          = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $total         = $gpodder->countActiveSubscriptions();
        $pages         = (int) ceil($total / self::SUBS_PER_PAGE);
        $offset        = ($page - 1) * self::SUBS_PER_PAGE;
        $subscriptions = $gpodder->listActiveSubscriptionsPage($offset, self::SUBS_PER_PAGE);

        ob_start();
        html_head('Painel', $gpodder->isLogged());

        if (isset($request->getQueryParams()['oktoken'])) {
            echo '<div class="alert alert-success" role="alert">Você está logado, pode fechar isso e voltar para o aplicativo.</div>';
        }

        require_once __DIR__ . '/../../views/dashboard/subscriptions.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function latestUpdates(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        ob_start();
        html_head('Últimas atualizações', $gpodder->isLogged());
        require_once __DIR__ . '/../../views/dashboard/latest-updates.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function devices(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        $db = $this->db;

        ob_start();
        html_head('Dispositivos', $gpodder->isLogged());
        require_once __DIR__ . '/../../views/dashboard/devices.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function profile(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');
        $error   = null;
        $success = null;
        $body    = $request->getParsedBody() ?? [];

        if (isset($body['change_password'])) {
            $newPassword     = $body['new_password'] ?? '';
            $confirmPassword = $body['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                $error = __('profile.passwords_dont_match');
            } else {
                try {
                    $this->userService->changePassword($gpodder->user, $body['current_password'] ?? '', $newPassword);
                    $gpodder->user->password = password_hash($newPassword, PASSWORD_DEFAULT);
                    $this->session->set('user', $gpodder->user);
                    $success                 = __('messages.password_changed');
                } catch (AuthException $e) {
                    $error = $e->getMessage();
                }
            }
        } elseif (isset($body['timezone'])) {
            try {
                $this->userService->updateTimezone((int) $gpodder->user->id, $body['timezone'] ?? '');
                $gpodder->user->timezone = $body['timezone'];
                $this->session->set('user', $gpodder->user);
                date_default_timezone_set($gpodder->user->timezone);
                $success = __('messages.timezone_updated');
            } catch (ValidationException $e) {
                $error = implode(' ', $e->getErrors());
            }
        } elseif (isset($body['language'])) {
            try {
                $this->userService->updateLanguage((int) $gpodder->user->id, $body['language'] ?? '');
                $gpodder->user->language = $body['language'];
                $this->session->set('user', $gpodder->user);
                $success                 = __('messages.language_updated');
            } catch (ValidationException $e) {
                $error = implode(' ', $e->getErrors());
            }
        }

        ob_start();
        html_head('Perfil', $gpodder->isLogged());
        require_once __DIR__ . '/../../views/dashboard/profile.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

}
