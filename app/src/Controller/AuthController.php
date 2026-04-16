<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Josantonius\Session\Session;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Database\DB;
use Sintoniza\Exception\AuthException;
use Sintoniza\Exception\ValidationException;
use Sintoniza\Service\MailService;
use Sintoniza\Service\UserService;

class AuthController
{
    public function __construct(
        private DB $db,
        private UserService $userService,
        private MailService $mailService,
        private Session $session
    ) {}

    public function showHome(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        if ($gpodder->isLogged()) {
            return new RedirectResponse('/dashboard');
        }

        ob_start();
        html_head();
        require_once __DIR__ . '/../../views/index.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function showLogin(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        if ($gpodder->isLogged()) {
            return new RedirectResponse('/dashboard');
        }

        $error = null;
        $body  = $request->getParsedBody() ?? [];

        if (!empty($body['login'])) {
            try {
                $user = $this->userService->authenticate($body['login'], $body['password'] ?? '');
                $gpodder->user = $user;
                $this->session->set('user', $user);

                $queryParams = $request->getQueryParams();
                if (!empty($queryParams['token'])) {
                    $token = $queryParams['token'];
                    $this->session->set('app_password', "{$token}:" . sha1($user->password . $token));
                }

                return new RedirectResponse(isset($queryParams['token']) ? '/dashboard?oktoken' : '/dashboard');
            } catch (AuthException $e) {
                $error = $e->getMessage();
            }
        }

        ob_start();
        html_head('Entrar');
        require_once __DIR__ . '/../../views/login.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function showRegister(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        if (!$this->userService->canSubscribe()) {
            ob_start();
            html_head('Registrar');
            echo '<div class="alert alert-success" role="alert">As assinaturas estão desabilitadas.</div>';
            html_foot();
            return new HtmlResponse(ob_get_clean());
        }

        $gpodder = $request->getAttribute('gpodder');
        $error   = null;
        $body    = $request->getParsedBody() ?? [];

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

        ob_start();
        html_head('Registrar');
        require_once __DIR__ . '/../../views/register.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function logout(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');
        $gpodder->logout();
        return new RedirectResponse('/');
    }

    public function showForgotPassword(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $message     = null;
        $messageType = 'success';
        $body        = $request->getParsedBody() ?? [];

        if ($request->getMethod() === 'POST' && !empty($body['email'])) {
            $user = $this->userService->generatePasswordResetToken(trim($body['email']));

            if ($user && !empty($user->reset_token)) {
                $resetLink = rtrim(BASE_URL, '/') . '/forget-password/reset?token=' . $user->reset_token;
                $this->mailService->sendPasswordReset($user->email, $user->name, $resetLink);
            }

            $message = __('forget_password.email_sent');
        }

        ob_start();
        html_head('Recuperar Senha');
        require_once __DIR__ . '/../../views/forget-password.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function showResetPassword(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
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

        ob_start();
        html_head('Recuperar Senha');
        require_once __DIR__ . '/../../views/forget-password/reset.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }
}
