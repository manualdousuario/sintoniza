<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Database\DB;
use Sintoniza\Exception\ValidationException;
use Sintoniza\Repository\UserRepository;
use Sintoniza\Service\UserService;

class AdminController
{
    private const USERS_PER_PAGE = 20;

    public function __construct(
        private DB $db,
        private UserService $userService,
        private UserRepository $userRepository
    ) {}

    public function index(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        $stats = [
            'users'         => (int) $this->db->firstColumn('SELECT COUNT(*) FROM users'),
            'subscriptions' => (int) $this->db->firstColumn('SELECT COUNT(*) FROM subscriptions WHERE deleted = 0'),
            'feeds'         => (int) $this->db->firstColumn('SELECT COUNT(*) FROM feeds'),
            'episodes'      => (int) $this->db->firstColumn('SELECT COUNT(*) FROM episodes'),
            'subs_7d'       => (int) $this->db->firstColumn(
                'SELECT COUNT(*) FROM subscriptions WHERE deleted = 0 AND changed >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))'
            ),
        ];

        $topFeeds = $this->db->all(
            'SELECT f.title, f.feed_url, COUNT(s.id) AS subscribers
             FROM subscriptions s
             JOIN feeds f ON s.feed = f.id
             WHERE s.deleted = 0
             GROUP BY f.id
             ORDER BY subscribers DESC
             LIMIT 10'
        );

        ob_start();
        html_head('Administração', $gpodder->isLogged());
        require_once __DIR__ . '/../../views/admin/index.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function users(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');
        $query   = $request->getQueryParams();
        $page    = max(1, (int) ($query['page'] ?? 1));
        $total   = $this->userRepository->count();
        $pages   = (int) ceil($total / self::USERS_PER_PAGE);
        $offset  = ($page - 1) * self::USERS_PER_PAGE;
        $users   = $this->userRepository->findPaginated($offset, self::USERS_PER_PAGE);
        $message = isset($query['deleted']) ? 'Usuário deletado com sucesso!' : null;

        ob_start();
        html_head('Usuários', $gpodder->isLogged());
        if ($message) {
            printf('<div class="alert alert-success" role="alert">%s</div>', htmlspecialchars($message));
        }
        require_once __DIR__ . '/../../views/admin/users.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function registerUser(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder     = $request->getAttribute('gpodder');
        $message     = null;
        $messageType = 'success';
        $body        = $request->getParsedBody() ?? [];

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
        html_head('Registrar Usuário', $gpodder->isLogged());
        if ($message) {
            printf('<div class="alert alert-%s" role="alert">%s</div>', $messageType, htmlspecialchars($message));
        }
        require_once __DIR__ . '/../../views/admin/register-user.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function editUser(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder     = $request->getAttribute('gpodder');
        $userId      = (int) $args['id'];
        $message     = null;
        $messageType = 'success';
        $body        = $request->getParsedBody() ?? [];

        if (isset($body['delete_user'])) {
            $this->userService->deleteUser($userId);
            return new RedirectResponse('/admin/users?deleted=1');
        }

        if (isset($body['toggle_active'])) {
            $active = (int) $body['toggle_active'] === 1;
            $this->userRepository->setActive($userId, $active);
            $message = $active ? 'Conta ativada com sucesso!' : 'Conta desabilitada com sucesso!';
        } elseif (isset($body['email'])) {
            $admin = isset($body['admin']) && $body['admin'] === '1';
            $this->userRepository->updateInfo($userId, trim($body['email']), $admin);
            $message = 'Informações atualizadas com sucesso!';
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return new RedirectResponse('/admin/users');
        }

        ob_start();
        html_head('Editar Usuário', $gpodder->isLogged());
        if ($message) {
            printf('<div class="alert alert-%s" role="alert">%s</div>', $messageType, htmlspecialchars($message));
        }
        require_once __DIR__ . '/../../views/admin/user.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }
}
