<?php

namespace Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class SessionMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $path = $request->getUri()->getPath();
        $publicRoutes = ['/', '/login', '/logout', '/verify-email'];
        if (in_array($path, $publicRoutes) || isset($_SESSION['username'])) {
            return $handler->handle($request);
        }
        $response = new SlimResponse();
        return $response->withHeader('Location', '/')->withStatus(302);
    }
}
