<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Dotenv\Dotenv;
use Hexlet\Code\Connection;
use Hexlet\Code\Url;
use Hexlet\Code\UrlRepository;
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
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    try {
        $conn = Connection::connect($dbUrl);
        dump('Соединение установлено!');

        return $conn;
    } catch (\PDOException $e) {
        dump($e->getMessage());
    }

});
$app = AppFactory::createFromContainer($container);
$router = $app->getRouteCollector()->getRouteParser();
$app->add(MethodOverrideMiddleware::class);
// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

// Add other middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $twig = $this->get(Twig::class);
    return $twig->render($response, 'home.html.twig',
        [
            'errors' => [],
            'url' => []
        ]);
})->setName('home');

$app->get('urls/{id}', function ($request, $response, $args) {
    $twig = $this->get(Twig::class);
    $urlRepository = $this->get(UrlRepository::class);
    $id = $args['id'];
    $url = $urlRepository->find($id);

    if (is_null($url)) {
        return $response->write('Page not found')->withStatus(404);
    }

    $messages = $this->get('flash')->getMessages();

    return $twig->render($response, 'show.html.twig',
    [
        'url' => $url,
        'flash' => $messages
    ]);
});

$app->post('/urls', function ($request, $response) use ($router) {
    $urlRepository = $this->get(UrlRepository::class);
    $url = Url::normalizeUrl($request->getParsedBodyParam('url')['name']);

    $v = new Valitron\Validator(['name' => $url]);
    $v->rule('required', 'name')->message('URL не может быть пустым')->rule('url', 'name')->message('Некорректный URL')->rule('length', 'name', 255)->message('URL не должен привышать 255 символов');

})->setName('urls.store');
$app->run();
