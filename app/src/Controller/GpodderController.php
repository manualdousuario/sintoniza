<?php

declare(strict_types=1);

namespace Sintoniza\Controller;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sintoniza\Api\GpodderApi;

class GpodderController
{
    public function __construct(private GpodderApi $api) {}

    public function handle(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $this->api->handleRequest();
        return new EmptyResponse();
    }
}
