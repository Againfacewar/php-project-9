<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Http\Response;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Psr7\Request;
use Slim\Views\PhpRenderer;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$container = new Container();
$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../resources/views');
});
$container->set('flash', function () {
    return new Messages();
});
$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

// Add other middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $twig = $this->get(Twig::class);

    return $twig->render($response, 'main.html.twig');
})->setName('home');

$app->run();
