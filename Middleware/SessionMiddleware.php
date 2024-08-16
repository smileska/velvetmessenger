<?php

namespace Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class SessionMiddleware
{
    private array $publicRoutes;

    public function __construct(array $publicRoutes = [])
    {
        $this->publicRoutes = $publicRoutes;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $path = $request->getUri()->getPath();

        if (empty($this->publicRoutes) || !in_array($path, $this->publicRoutes)) {
            if (!isset($_SESSION['username'])) {
                $response = new SlimResponse();
                return $response->withHeader('Location', '/')->withStatus(302);
            }
        }

        return $handler->handle($request);
    }
}
