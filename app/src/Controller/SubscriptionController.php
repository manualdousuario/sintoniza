<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Database\DB;

class SubscriptionController
{
    private const PER_PAGE = 20;

    public function __construct(
        private DB $db,
        private Engine $plates
    ) {}

    private function baseData(ServerRequestInterface $request): array
    {
        $gpodder = $request->getAttribute('gpodder');
        return [
            'logged'  => $gpodder->isLogged(),
            'isAdmin' => $gpodder->user && (int) $gpodder->user->admin === 1,
        ];
    }

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

        return new HtmlResponse($this->plates->render('subscription::show', $this->baseData($request) + [
            'title'        => $subscription->title ?? 'Assinatura',
            'subscription' => $subscription,
            'episodes'     => $episodes,
            'page'         => $page,
            'pages'        => $pages,
        ]));
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

        return new HtmlResponse($this->plates->render('subscription::episode', $this->baseData($request) + [
            'title'        => $episode->title ?? 'Episódio',
            'subscription' => $subscription,
            'episode'      => $episode,
            'actions'      => $actions,
        ]));
    }
}
