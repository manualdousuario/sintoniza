<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Josantonius\Session\Session;
use Laminas\Diactoros\Response\HtmlResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Cache\CacheInterface;
use Sintoniza\Database\DB;
use Sintoniza\Exception\AuthException;
use Sintoniza\Exception\ValidationException;
use Sintoniza\Library\Language;
use Sintoniza\Service\UserService;

class DashboardController
{
    public function __construct(
        private DB $db,
        private UserService $userService,
        private Session $session,
        private Engine $plates,
        private CacheInterface $cache
    ) {}

    private const SUBS_PER_PAGE         = 20;
    private const ACTIONS_PER_PAGE      = 20;
    private const ACTIONS_COUNT_TTL     = 30;

    private function isAdmin(ServerRequestInterface $request): bool
    {
        $gpodder = $request->getAttribute('gpodder');
        return $gpodder->user && (int) $gpodder->user->admin === 1;
    }

    public function index(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        $page          = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $total         = $gpodder->countActiveSubscriptions();
        $pages         = (int) ceil($total / self::SUBS_PER_PAGE);
        $offset        = ($page - 1) * self::SUBS_PER_PAGE;
        $subscriptions = $gpodder->listActiveSubscriptionsPage($offset, self::SUBS_PER_PAGE);

        return new HtmlResponse($this->plates->render('dashboard::subscriptions', [
            'logged'        => $gpodder->isLogged(),
            'isAdmin'       => $this->isAdmin($request),
            'subscriptions' => $subscriptions,
            'page'          => $page,
            'pages'         => $pages,
            'userName'      => $gpodder->user->name,
            'okToken'       => isset($request->getQueryParams()['oktoken']),
        ]));
    }

    public function latestUpdates(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        $page    = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $total   = $this->cache->remember(
            "dashboard:actions_count:{$gpodder->user->id}",
            self::ACTIONS_COUNT_TTL,
            fn() => $gpodder->countActions()
        );
        $pages   = (int) ceil($total / self::ACTIONS_PER_PAGE);
        $offset  = ($page - 1) * self::ACTIONS_PER_PAGE;
        $actions = $gpodder->listActionsPage($offset, self::ACTIONS_PER_PAGE);

        return new HtmlResponse($this->plates->render('dashboard::latest-updates', [
            'logged'  => $gpodder->isLogged(),
            'isAdmin' => $this->isAdmin($request),
            'actions' => $actions,
            'page'    => $page,
            'pages'   => $pages,
        ]));
    }

    public function devices(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');
        $devices = $this->db->all('SELECT * FROM devices WHERE user = ? ORDER BY name', $gpodder->user->id);

        return new HtmlResponse($this->plates->render('dashboard::devices', [
            'logged'  => $gpodder->isLogged(),
            'isAdmin' => $this->isAdmin($request),
            'devices' => $devices,
        ]));
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

        $lang = Language::getInstance();

        return new HtmlResponse($this->plates->render('dashboard::profile', [
            'logged'             => $gpodder->isLogged(),
            'isAdmin'            => $this->isAdmin($request),
            'error'              => $error,
            'success'            => $success,
            'currentTimezone'    => $gpodder->user->timezone,
            'currentLang'        => $lang->getCurrentLanguage(),
            'availableLanguages' => $lang->getAvailableLanguages(),
            'userToken'          => $gpodder->getUserToken(),
        ]));
    }
}
