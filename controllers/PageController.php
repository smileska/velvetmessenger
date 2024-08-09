<?php

namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PageController
{
    private $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function home(Request $request, Response $response): Response
    {
        $html = view('index.view.php');
        $response->getBody()->write($html);
        return $response;
    }
    public function notes(Request $request, Response $response): Response
    {
        $html = view('index.view.php');
        $response->getBody()->write($html);
        return $response;
    }
}
