<?php

declare(strict_types=1);

use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Sintoniza\Controller\AdminController;
use Sintoniza\Controller\AuthController;
use Sintoniza\Controller\DashboardController;
use Sintoniza\Controller\GpodderController;
use Sintoniza\Controller\SubscriptionController;
use Sintoniza\Library\Container;
use Sintoniza\Library\Logger;
use Sintoniza\Middleware\AdminMiddleware;
use Sintoniza\Middleware\AuthMiddleware;

function buildRouter(): Router
{
    $container = Container::getInstance();
    $strategy  = (new ApplicationStrategy())->setContainer($container);
    $router    = new Router();
    $router->setStrategy($strategy);

    // Public routes
    $router->map('GET',  '/',                       [AuthController::class, 'showHome']);
    $router->map('GET',  '/login',                  [AuthController::class, 'showLogin']);
    $router->map('POST', '/login',                  [AuthController::class, 'showLogin']);
    $router->map('GET',  '/register',               [AuthController::class, 'showRegister']);
    $router->map('POST', '/register',               [AuthController::class, 'showRegister']);
    $router->map('GET',  '/logout',                 [AuthController::class, 'logout']);
    $router->map('GET',  '/forget-password',        [AuthController::class, 'showForgotPassword']);
    $router->map('POST', '/forget-password',        [AuthController::class, 'showForgotPassword']);
    $router->map('GET',  '/forget-password/reset',  [AuthController::class, 'showResetPassword']);
    $router->map('POST', '/forget-password/reset',  [AuthController::class, 'showResetPassword']);

    // Subscription routes
    $router->group('/subscription', function ($group) {
        $group->map('GET', '/{id:number}',                       [SubscriptionController::class, 'show']);
        $group->map('GET', '/{id:number}/episode/{episodeId:number}', [SubscriptionController::class, 'episode']);
    })->middleware(new AuthMiddleware());

    // Protected routes
    $router->group('/dashboard', function ($group) {
        $group->map('GET',  '/',                        [DashboardController::class, 'index']);
        $group->map('GET',  '/profile',                 [DashboardController::class, 'profile']);
        $group->map('POST', '/profile',                 [DashboardController::class, 'profile']);
        $group->map('GET',  '/profile/latest-updates',  [DashboardController::class, 'latestUpdates']);
        $group->map('GET',  '/profile/devices',         [DashboardController::class, 'devices']);
    })->middleware(new AuthMiddleware());

    // Admin routes
    $router->group('/admin', function ($group) {
        $group->map('GET',  '/', [AdminController::class, 'index']);
        $group->map('POST', '/', [AdminController::class, 'index']);
    })->middleware(new AuthMiddleware())->middleware(new AdminMiddleware());

    // GPodder API routes
    $router->map('POST', '/api/2/auth/{rest:.+}',                      [GpodderController::class, 'handle']);
    $router->map('GET',  '/api/2/{rest:.+}',                           [GpodderController::class, 'handle']);
    $router->map('POST', '/api/2/{rest:.+}',                           [GpodderController::class, 'handle']);
    $router->map('GET',  '/suggestions/{rest:.+}',                     [GpodderController::class, 'handle']);
    $router->map('GET',  '/subscriptions/{rest:.+}',                   [GpodderController::class, 'handle']);
    $router->map('GET',  '/toplist/{rest:.+}',                         [GpodderController::class, 'handle']);
    $router->map('POST', '/index.php/login/v2',                        [GpodderController::class, 'handle']);
    $router->map('POST', '/index.php/login/v2/poll',                   [GpodderController::class, 'handle']);
    $router->map('GET',  '/index.php/apps/gpoddersync/{rest:.+}',      [GpodderController::class, 'handle']);
    $router->map('POST', '/index.php/apps/gpoddersync/{rest:.+}',      [GpodderController::class, 'handle']);

    return $router;
}
