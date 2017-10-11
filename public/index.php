<?php
session_start();
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
require '../vendor/autoload.php';
require '../src/config/db.php';
require '../src/config/emailApp.php';
require '../src/config/mcrypt.php';
$c = new \Slim\Container();
$c['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        return $c['response']->withStatus(500)
                             ->withHeader('Content-Type', 'text/html')
                             ->write($request);
    };
};
$app = new Slim\App($c);
$app->get('/', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
});
require '../src/routes/apis.php';
$app->run();
?>