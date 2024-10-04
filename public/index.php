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

$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../resources/views');
});
$container->set('flash', function () {
    return new Messages();
});
$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);

$app->get('/', function ($request, $response) {
    return $response->write('Hello, hexlet!');
});

$app->run();
