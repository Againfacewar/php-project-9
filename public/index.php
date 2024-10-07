<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Hexlet\Code\Connection;
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

$container->set(\PDO::class, function () {
    $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    $scheme = null;
    $user = '';
    $password = '';
    $host = null;
    $port = null;
    $dbName = null;
    $conn = null;

    if ($dbUrl) {
        $databaseUrl = parse_url($dbUrl);
        $scheme = $databaseUrl['scheme'] ?? 'pgsql';
        $user = $databaseUrl['user'];
        $password = $databaseUrl['pass'];
        $host = $databaseUrl['host'];
        $port = $databaseUrl['port'];
        $dbName = ltrim($databaseUrl['path'], '/');
    } else {
        $scheme = 'pgsql';
        $host = 'localhost';
        $port = 5432;
        $dbName = 'hexlet';
    }
    $dsn = Connection::buildDsn($scheme, $host, $port, $dbName);
    try {
        $conn = new \PDO($dsn, $user, $password);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        echo "Соединение установлено!";
    } catch (\PDOException $e) {
        echo "Ошибка подключения: " . $e->getMessage();
    }

    return $conn;
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
    dump($this->get(\PDO::class));
    return $twig->render($response, 'main.html.twig',
        [
            'errors' => [],
            'url' => []
        ]);
})->setName('home');

$app->run();
