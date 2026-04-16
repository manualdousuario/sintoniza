<?php

declare(strict_types=1);

namespace Sintoniza\Library;

use GuzzleHttp\Client;
use Josantonius\Session\Session;
use League\Container\Container as LeagueContainer;
use League\Container\ReflectionContainer;
use Monolog\Logger as MonologLogger;
use Sintoniza\Api\GpodderApi;
use Sintoniza\Controller\AdminController;
use Sintoniza\Controller\AuthController;
use Sintoniza\Controller\DashboardController;
use Sintoniza\Controller\GpodderController;
use Sintoniza\Database\DB;
use Sintoniza\Repository\EpisodeRepository;
use Sintoniza\Repository\FeedRepository;
use Sintoniza\Repository\SubscriptionRepository;
use Sintoniza\Repository\UserRepository;
use Sintoniza\Feed\PodcastIndexClient;
use Sintoniza\Service\FeedService;
use Sintoniza\Service\MailService;
use Sintoniza\Service\UserService;

class Container
{
    private static ?LeagueContainer $instance = null;

    public static function getInstance(): LeagueContainer
    {
        if (self::$instance === null) {
            $container = new LeagueContainer();
            $container->delegate(new ReflectionContainer(true));

            // Logger
            $container->add(MonologLogger::class, fn() => Logger::getInstance());

            // Session
            $container->add(Session::class, fn() => new Session())->setShared(true);

            // HTTP Client
            $container->add(Client::class, fn() => new Client([
                'timeout'         => 30,
                'allow_redirects' => ['max' => 5],
                'headers'         => ['User-Agent' => 'Sintoniza'],
                'verify'          => true,
            ]))->setShared(true);

            // Database
            $container->add(DB::class, fn() => new DB(
                MYSQL_HOST,
                MYSQL_DATABASE,
                MYSQL_USER,
                MYSQL_PASSWORD,
                MYSQL_PORT
            ))->setShared(true);

            // Repositories
            $container->add(UserRepository::class)->addArgument(DB::class);
            $container->add(FeedRepository::class)->addArgument(DB::class);
            $container->add(EpisodeRepository::class)->addArgument(DB::class);
            $container->add(SubscriptionRepository::class)->addArgument(DB::class);

            // Services
            $container->add(UserService::class)
                ->addArgument(DB::class)
                ->addArgument(UserRepository::class);

            $container->add(FeedService::class, function () use ($container) {
                $piClient = PODCAST_INDEX_API_KEY && PODCAST_INDEX_API_SECRET
                    ? new PodcastIndexClient($container->get(Client::class), PODCAST_INDEX_API_KEY, PODCAST_INDEX_API_SECRET)
                    : null;

                return new FeedService(
                    $container->get(DB::class),
                    $container->get(FeedRepository::class),
                    $container->get(MonologLogger::class),
                    $container->get(Client::class),
                    $piClient
                );
            })->setShared(true);

            $container->add(MailService::class);

            // Controllers
            $container->add(AuthController::class)
                ->addArgument(DB::class)
                ->addArgument(UserService::class)
                ->addArgument(MailService::class)
                ->addArgument(Session::class);

            $container->add(DashboardController::class)
                ->addArgument(DB::class)
                ->addArgument(UserService::class)
                ->addArgument(Session::class);

            $container->add(AdminController::class)
                ->addArgument(DB::class)
                ->addArgument(UserService::class);

            $container->add(GpodderApi::class)
                ->addArgument(DB::class)
                ->addArgument(MonologLogger::class)
                ->addArgument(Client::class)
                ->addArgument(Session::class)
                ->setShared(true);

            $container->add(GpodderController::class)
                ->addArgument(GpodderApi::class);

            self::$instance = $container;
        }

        return self::$instance;
    }

    private function __construct() {}
}
