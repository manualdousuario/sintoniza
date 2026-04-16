<?php

declare(strict_types=1);

namespace Sintoniza\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');
        $user    = $gpodder?->user;

        if (!$user || (int) ($user->admin ?? 0) !== 1) {
            return new RedirectResponse('/dashboard');
        }

        return $handler->handle($request);
    }
}
