<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use DI\Container;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Hexlet\Code\Connection;
use Hexlet\Code\Url;
use Hexlet\Code\UrlCheck;
use Hexlet\Code\UrlCheckRepository;
use Hexlet\Code\UrlRepository;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Http\Response;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Psr7\Request;
use Slim\Views\PhpRenderer;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

session_start();
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
        error_log($e->getMessage());
    }
});

$container->set(Client::class, function () {
    return new Client();
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
    $urlCheckRepository = $this->get(UrlCheckRepository::class);
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
        'flash' => $messages,
        'urlChecks' => $url->getUrlChecksByUrlId($urlCheckRepository)
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

$app->post('/urls/{id}/checks', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $urlCheckRepository = $this->get(UrlCheckRepository::class);
    $url = $urlRepository->find($id);
    $client = $this->get(Client::class);
    $statusCode = null;

    if (!$url) {
        return $response->write('Page not found')->withStatus(404);
    }

    try {
        $res = $client->request('GET', $url->getName());
        $statusCode = $res->getStatusCode();
    } catch (ConnectException $e) {
        error_log($e->getMessage());
        $this->get('flash')->addMessage('success', 'Network error: ' . $e->getMessage());
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
    } catch (ClientException $e) {
        if ($e->hasResponse()) {
            $statusCode = $e->getResponse()->getStatusCode();
            error_log($e->getMessage());
        } else {
            error_log($e->getMessage());
        }
    }

    $urlCheck = UrlCheck::fromArray([$id, $statusCode, null, null, null, Carbon::now()]);
    $urlCheckRepository->save($urlCheck);
    $this->get('flash')->addMessage('success', 'Страница успешно проверена');

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
})->setName('urls.checks');

$app->get('/urls', function ($request, $response) {
    $twig = $this->get(Twig::class);
    $urlRepository = $this->get(UrlRepository::class);
    $urlCheckRepository = $this->get(UrlCheckRepository::class);
    $urls = $urlRepository->listUrls();
    if (!empty($urls)) {
        $urls = collect($urls)->map(function ($url) use ($urlCheckRepository) {
            $urlChecks = $url->getUrlChecksByUrlId($urlCheckRepository);
            if (is_array($urlChecks)) {
                $url->setUrlChecks($urlChecks);
            }
            return $url;
        })->toArray();
    }


    return $twig->render(
        $response,
        'index.html.twig',
        [
            'urls' => $urls
        ]
    );
})->setName('urls.index');

$app->run();
