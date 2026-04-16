<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Database\DB;
use Sintoniza\Exception\ValidationException;
use Sintoniza\Service\UserService;

class AdminController
{
    public function __construct(
        private DB $db,
        private UserService $userService
    ) {}

    public function index(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder     = $request->getAttribute('gpodder');
        $message     = null;
        $messageType = 'success';
        $body        = $request->getParsedBody() ?? [];

        if (isset($body['delete_user'])) {
            $userId = (int) $body['delete_user'];
            $this->userService->deleteUser($userId);
            $message = 'Usuário deletado com sucesso!';
        }

        if (isset($body['new_username'], $body['new_password'], $body['new_email'])) {
            try {
                $this->userService->register(
                    $body['new_username'],
                    $body['new_password'],
                    $body['new_email']
                );
                $message = 'Usuário registrado com sucesso!';
            } catch (ValidationException $e) {
                $message     = implode(' ', $e->getErrors());
                $messageType = 'danger';
            }
        }

        ob_start();
        html_head('Administration', $gpodder->isLogged());

        if ($message) {
            printf('<div class="alert alert-%s" role="alert">%s</div>', $messageType, htmlspecialchars($message));
        }

        require_once __DIR__ . '/../../views/admin.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }
}
