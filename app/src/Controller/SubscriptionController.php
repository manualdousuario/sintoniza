<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Database\DB;

class SubscriptionController
{
    private const PER_PAGE = 20;

    public function __construct(private DB $db) {}

    public function show(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder        = $request->getAttribute('gpodder');
        $subscriptionId = (int) ($args['id'] ?? 0);
        $subscription   = $gpodder->getSubscriptionWithFeed($subscriptionId);

        if (!$subscription) {
            return new RedirectResponse('/dashboard');
        }

        $page     = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $offset   = ($page - 1) * self::PER_PAGE;
        $total    = 0;
        $pages    = 0;
        $episodes = [];

        if ($subscription->feed_id) {
            $total    = $gpodder->countEpisodesByFeed((int) $subscription->feed_id);
            $pages    = (int) ceil($total / self::PER_PAGE);
            $episodes = $gpodder->listEpisodesByFeed((int) $subscription->feed_id, $offset, self::PER_PAGE);
        }

        ob_start();
        html_head(htmlspecialchars($subscription->title ?? 'Assinatura'), $gpodder->isLogged());
        require_once __DIR__ . '/../../views/subscription/show.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }

    public function episode(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $gpodder        = $request->getAttribute('gpodder');
        $subscriptionId = (int) ($args['id'] ?? 0);
        $episodeId      = (int) ($args['episodeId'] ?? 0);
        $subscription   = $gpodder->getSubscriptionWithFeed($subscriptionId);

        if (!$subscription) {
            return new RedirectResponse('/dashboard');
        }

        $episode = $this->db->firstRow(
            'SELECT * FROM episodes WHERE id = ? AND feed = ?',
            $episodeId,
            $subscription->feed_id
        );

        if (!$episode) {
            return new RedirectResponse('/subscription/' . $subscriptionId);
        }

        $actions = $gpodder->listEpisodeActions($subscriptionId, $episodeId);

        ob_start();
        html_head(htmlspecialchars($episode->title ?? 'Episódio'), $gpodder->isLogged());
        require_once __DIR__ . '/../../views/subscription/episode.php';
        html_foot();
        return new HtmlResponse(ob_get_clean());
    }
}
