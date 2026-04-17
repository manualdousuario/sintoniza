<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Cache\CacheInterface;
use Sintoniza\Database\DB;
use Sintoniza\Exception\ValidationException;
use Sintoniza\Repository\FeedRepository;
use Sintoniza\Repository\UserRepository;
use Sintoniza\Service\UserService;

class AdminController
{
    private const USERS_PER_PAGE = 20;
    private const FEEDS_PER_PAGE = 20;
    private const STATS_TTL      = 300;
    private const TOP_FEEDS_TTL  = 900;

    public function __construct(
        private DB $db,
        private UserService $userService,
        private UserRepository $userRepository,
        private FeedRepository $feedRepository,
        private Engine $plates,
        private CacheInterface $cache
    ) {}

    private function baseData(ServerRequestInterface $request): array
    {
        $gpodder = $request->getAttribute('gpodder');
        return [
            'logged'  => $gpodder->isLogged(),
            'isAdmin' => $gpodder->user && (int) $gpodder->user->admin === 1,
        ];
    }

    public function index(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $stats = $this->cache->remember('admin:stats', self::STATS_TTL, function () {
            $row = $this->db->firstRow(
                'SELECT
                    (SELECT COUNT(*) FROM users)                                            AS users,
                    (SELECT COUNT(*) FROM subscriptions WHERE deleted = 0)                  AS subscriptions,
                    (SELECT COUNT(*) FROM feeds)                                            AS feeds,
                    (SELECT COUNT(*) FROM episodes)                                         AS episodes,
                    (SELECT COUNT(*) FROM subscriptions
                        WHERE deleted = 0
                          AND changed >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY)))   AS subs_7d'
            );

            return [
                'users'         => (int) ($row->users ?? 0),
                'subscriptions' => (int) ($row->subscriptions ?? 0),
                'feeds'         => (int) ($row->feeds ?? 0),
                'episodes'      => (int) ($row->episodes ?? 0),
                'subs_7d'       => (int) ($row->subs_7d ?? 0),
            ];
        });

        $topFeeds = $this->cache->remember('admin:top_feeds', self::TOP_FEEDS_TTL, fn() => $this->db->all(
            'SELECT f.title, f.feed_url, COUNT(s.id) AS subscribers
             FROM subscriptions s
             JOIN feeds f ON s.feed = f.id
             WHERE s.deleted = 0
             GROUP BY f.id
             ORDER BY subscribers DESC
             LIMIT 10'
        ));

        return new HtmlResponse($this->plates->render('admin::index', $this->baseData($request) + [
            'stats'    => $stats,
            'topFeeds' => $topFeeds,
        ]));
    }

    public function users(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $query   = $request->getQueryParams();
        $page    = max(1, (int) ($query['page'] ?? 1));
        $search  = isset($query['q']) ? trim((string) $query['q']) : '';
        $active  = $this->parseActive($query['active'] ?? null);

        $total   = $this->userRepository->countFiltered($search !== '' ? $search : null, $active);
        $pages   = max(1, (int) ceil($total / self::USERS_PER_PAGE));
        $page    = min($page, $pages);
        $offset  = ($page - 1) * self::USERS_PER_PAGE;
        $users   = $this->userRepository->findFiltered(
            $search !== '' ? $search : null,
            $active,
            $offset,
            self::USERS_PER_PAGE
        );
        $message = isset($query['deleted']) ? 'Usuário deletado com sucesso!' : null;

        return new HtmlResponse($this->plates->render('admin::users', $this->baseData($request) + [
            'users'       => $users,
            'page'        => $page,
            'pages'       => $pages,
            'total'       => $total,
            'search'      => $search,
            'active'      => $active,
            'message'     => $message,
            'messageType' => 'success',
        ]));
    }

    public function subscriptions(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $query  = $request->getQueryParams();
        $page   = max(1, (int) ($query['page'] ?? 1));
        $search = isset($query['q']) ? trim((string) $query['q']) : '';
        $active = $this->parseActive($query['active'] ?? null);

        $total  = $this->feedRepository->countFiltered($search !== '' ? $search : null, $active);
        $pages  = max(1, (int) ceil($total / self::FEEDS_PER_PAGE));
        $page   = min($page, $pages);
        $offset = ($page - 1) * self::FEEDS_PER_PAGE;
        $feeds  = $this->feedRepository->findFiltered(
            $search !== '' ? $search : null,
            $active,
            $offset,
            self::FEEDS_PER_PAGE
        );

        return new HtmlResponse($this->plates->render('admin::subscriptions', $this->baseData($request) + [
            'feeds'  => $feeds,
            'page'   => $page,
            'pages'  => $pages,
            'total'  => $total,
            'search' => $search,
            'active' => $active,
        ]));
    }

    public function toggleSubscription(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $feedId = (int) $args['id'];
        $feed   = $this->feedRepository->findById($feedId);

        if (!$feed) {
            return new RedirectResponse('/admin/subscriptions');
        }

        $body   = $request->getParsedBody() ?? [];
        $active = isset($body['active']) ? (int) $body['active'] === 1 : !((int) $feed->active === 1);

        $this->feedRepository->setActive($feedId, $active);

        $query = $request->getQueryParams();
        $qs    = array_filter([
            'q'      => $query['q']      ?? null,
            'active' => $query['active'] ?? null,
            'page'   => $query['page']   ?? null,
        ], fn($v) => $v !== null && $v !== '');

        $target = '/admin/subscriptions' . ($qs ? '?' . http_build_query($qs) : '');
        return new RedirectResponse($target);
    }

    private function parseActive(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return in_array($value, ['1', 1, true, 'true'], true) ? 1 : 0;
    }

    public function registerUser(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
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

        return new HtmlResponse($this->plates->render('admin::register-user', $this->baseData($request) + [
            'message'     => $message,
            'messageType' => $messageType,
        ]));
    }

    public function editUser(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $userId      = (int) $args['id'];
        $message     = null;
        $messageType = 'success';
        $body        = $request->getParsedBody() ?? [];

        if (isset($body['delete_user'])) {
            $this->userService->deleteUser($userId);
            return new RedirectResponse('/admin/users?deleted=1');
        }

        if (isset($body['toggle_active'])) {
            $active  = (int) $body['toggle_active'] === 1;
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

        return new HtmlResponse($this->plates->render('admin::user', $this->baseData($request) + [
            'user'        => $user,
            'message'     => $message,
            'messageType' => $messageType,
        ]));
    }
}
