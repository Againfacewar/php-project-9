<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
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
    $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

    try {
        $conn = Connection::connect($dbUrl);

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
    return $twig->render(
        $response,
        'home.html.twig',
        [
            'errors' => [],
            'url' => []
        ]
    );
})->setName('home');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $twig = $this->get(Twig::class);
    $urlRepository = $this->get(UrlRepository::class);
    $id = $args['id'];
    $url = $urlRepository->find($id);

    if (is_null($url)) {
        return $response->write('Page not found')->withStatus(404);
    }
    $messages = $this->get('flash')->getMessages();

    return $twig->render(
        $response,
        'show.html.twig',
        [
        'url' => $url,
        'flash' => $messages
        ]
    );
})->setName('urls.show');

$app->post('/urls', function ($request, $response) use ($router) {
    $urlRepository = $this->get(UrlRepository::class);
    $data = $request->getParsedBodyParam('url');
    $twig = $this->get(Twig::class);
    $v = new Valitron\Validator($data);
    $v->rule('required', 'name')
        ->message('URL не может быть пустым')
        ->rule('url', 'name')
        ->message('Некорректный URL')
        ->rule('lengthMax', 'name', 255)
        ->message('URL не должен привышать 255 символов');

    if ($v->validate()) {
        $data['name'] = Url::normalizeUrl($data['name']);
        $url = $urlRepository->findByName($data['name']);
        if ($url) {
            $this->get('flash')->addMessage('success', 'Страница уже существует!');
        } else {
            $url = Url::fromArray([$data['name'], Carbon::now()]);
            $urlRepository->save($url);
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        }

        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]));
    }

    return $twig->render(
        $response->withStatus(422),
        'home.html.twig',
        [
            'errors' => $v->errors(),
            'url' => $data
        ]
    );
})->setName('urls.store');

$app->get('/urls', function ($request, $response) {
    $twig = $this->get(Twig::class);
    $urls = $this->get(UrlRepository::class)->listUrls();


    return $twig->render(
        $response,
        'index.html.twig',
        [
            'urls' => $urls
        ]
    );
})->setName('urls.index');

$app->run();
