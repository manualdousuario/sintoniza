<?php

declare(strict_types=1);

namespace Sintoniza\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $gpodder = $request->getAttribute('gpodder');

        if (!$gpodder || !$gpodder->isLogged()) {
            return new RedirectResponse('/login');
        }

        return $handler->handle($request);
    }
}
