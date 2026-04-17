<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Josantonius\Session\Session;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Database\DB;
use Sintoniza\Exception\AuthException;
use Sintoniza\Exception\ValidationException;
use Sintoniza\Library\Language;
use Sintoniza\Service\MailService;
use Sintoniza\Service\UserService;

class AuthController
{
    public function __construct(
        private DB $db,
        private UserService $userService,
        private MailService $mailService,
        private Session $session,
        private Engine $plates
    ) {}

    private function isAdmin(ServerRequestInterface $request): bool
    {
        $gpodder = $request->getAttribute('gpodder');
        return $gpodder->user && (int) $gpodder->user->admin === 1;
    }

    public function showHome(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        if ($gpodder->isLogged()) {
            return new RedirectResponse('/dashboard');
        }

        return new HtmlResponse($this->plates->render('index', [
            'logged'  => false,
            'isAdmin' => false,
        ]));
    }

    public function showLogin(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        if ($gpodder->isLogged()) {
            return new RedirectResponse('/dashboard');
        }

        $error       = null;
        $body        = $request->getParsedBody() ?? [];
        $queryParams = $request->getQueryParams();

        if (!empty($body['login'])) {
            try {
                $user = $this->userService->authenticate($body['login'], $body['password'] ?? '');
                $gpodder->user = $user;
                $this->session->set('user', $user);

                if (!empty($queryParams['token'])) {
                    $token = $queryParams['token'];
                    $this->session->set('app_password', "{$token}:" . sha1($user->password . $token));
                }

                return new RedirectResponse(isset($queryParams['token']) ? '/dashboard?oktoken' : '/dashboard');
            } catch (AuthException $e) {
                $error = $e->getMessage();
            }
        }

        return new HtmlResponse($this->plates->render('auth::login', [
            'logged'   => false,
            'isAdmin'  => false,
            'error'    => $error,
            'hasToken' => isset($queryParams['token']),
        ]));
    }

    public function showRegister(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');
        $error   = null;
        $notice  = null;
        $body    = $request->getParsedBody() ?? [];

        if (!$this->userService->canSubscribe()) {
            return new HtmlResponse($this->plates->render('auth::register', [
                'logged'   => $gpodder->isLogged(),
                'isAdmin'  => $this->isAdmin($request),
                'disabled' => true,
                'notice'   => 'As assinaturas estão desabilitadas.',
                'error'    => null,
                'captcha'  => null,
            ]));
        }

        if (!empty($body['username'])) {
            if (!$this->userService->checkCaptcha($body['captcha'] ?? '', $body['cc'] ?? '')) {
                $error = __('messages.invalid_captcha');
            } else {
                try {
                    $this->userService->register(
                        $body['username'] ?? '',
                        $body['password'] ?? '',
                        $body['email'] ?? ''
                    );
                    return new RedirectResponse('/login');
                } catch (ValidationException $e) {
                    $error = implode(' ', $e->getErrors());
                }
            }
        }

        return new HtmlResponse($this->plates->render('auth::register', [
            'logged'   => $gpodder->isLogged(),
            'isAdmin'  => $this->isAdmin($request),
            'disabled' => false,
            'notice'   => $notice,
            'error'    => $error,
            'captcha'  => $gpodder->generateCaptcha(),
        ]));
    }

    public function logout(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');
        $gpodder->logout();
        return new RedirectResponse('/');
    }

    public function switchLanguage(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder  = $request->getAttribute('gpodder');
        $body     = $request->getParsedBody() ?? [];
        $language = is_string($body['language'] ?? null) ? $body['language'] : '';

        $available = Language::getInstance()->getAvailableLanguages();
        if (array_key_exists($language, $available)) {
            if ($gpodder->isLogged()) {
                try {
                    $this->userService->updateLanguage((int) $gpodder->user->id, $language);
                    $gpodder->user->language = $language;
                    $this->session->set('user', $gpodder->user);
                } catch (ValidationException) {
                }
            } else {
                $this->session->set('language', $language);
                Language::getInstance()->setLanguage($language);
            }
        }

        return new RedirectResponse($this->safeReferer($request));
    }

    private function safeReferer(ServerRequestInterface $request): string
    {
        $referer = $request->getHeaderLine('Referer');
        if ($referer === '') {
            return '/';
        }

        $parsed = parse_url($referer);
        if ($parsed === false || empty($parsed['path'])) {
            return '/';
        }

        $target = $parsed['path'];
        if (!empty($parsed['query'])) {
            $target .= '?' . $parsed['query'];
        }

        return $target;
    }

    public function showForgotPassword(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder     = $request->getAttribute('gpodder');
        $message     = null;
        $messageType = 'success';
        $body        = $request->getParsedBody() ?? [];

        if ($request->getMethod() === 'POST' && !empty($body['email'])) {
            $user = $this->userService->generatePasswordResetToken(trim($body['email']));

            if ($user && !empty($user->reset_token)) {
                $resetLink  = rtrim(BASE_URL, '/') . '/forget-password/reset?token=' . $user->reset_token;
                $userLang   = isset($user->language) && is_string($user->language) && $user->language !== ''
                    ? $user->language
                    : Language::getInstance()->getCurrentLanguage();
                $this->mailService->sendPasswordReset($user->email, $user->name, $resetLink, $userLang);
            }

            $message = __('forget_password.email_sent');
        }

        return new HtmlResponse($this->plates->render('auth::forget-password', [
            'logged'      => $gpodder->isLogged(),
            'isAdmin'     => $this->isAdmin($request),
            'message'     => $message,
            'messageType' => $messageType,
        ]));
    }

    public function showResetPassword(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder     = $request->getAttribute('gpodder');
        $message     = null;
        $messageType = 'success';
        $body        = $request->getParsedBody() ?? [];
        $query       = $request->getQueryParams();

        if ($request->getMethod() === 'POST') {
            $newPassword = $body['new_password'] ?? '';
            $token       = $query['token'] ?? '';

            if (strlen($newPassword) < 8) {
                $message     = 'A nova senha é muito curta (mínimo 8 caracteres).';
                $messageType = 'danger';
            } else {
                try {
                    $this->userService->resetPassword($token, $newPassword);
                    $message = 'Sua senha foi redefinida com sucesso. Você pode fazer login agora.';
                } catch (AuthException $e) {
                    $message     = $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }

        return new HtmlResponse($this->plates->render('auth::forget-password/reset', [
            'logged'      => $gpodder->isLogged(),
            'isAdmin'     => $this->isAdmin($request),
            'message'     => $message,
            'messageType' => $messageType,
        ]));
    }
}
