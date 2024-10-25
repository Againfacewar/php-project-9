<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use DI\Container;
use DiDom\Document;
use DiDom\Element;
use DiDom\Query;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Hexlet\Code\Connection;
use Hexlet\Code\Url;
use Hexlet\Code\UrlCheck;
use Hexlet\Code\UrlCheckRepository;
use Hexlet\Code\UrlRepository;
use Illuminate\Support\Collection;
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

$container->set(Document::class, function () {
    return new Document();
});

$container->set(\PDO::class, function () {
    $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        if ($dbUrl) {
            $conn = Connection::createFromUrl($dbUrl);
        } else {
            $conn = new Connection(
                $_ENV['DB_HOST'] ?? getenv('DB_HOST'),
                $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE'),
                $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME'),
                $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD'),
                $_ENV['DB_PORT'] ?? getenv('DB_PORT'),
            );
        }

        return $conn->connect();
    } catch (\PDOException | InvalidPathException $e) {
        error_log($e->getMessage());
        dump($e->getMessage());
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
            $this->get('flash')->addMessage('error', 'Страница уже существует!');
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

$app->post('/urls/{id}/checks', callable: function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $urlCheckRepository = $this->get(UrlCheckRepository::class);
    /** @var Document $document */
    $document = $this->get(Document::class);
    $url = $urlRepository->find($id);
    $client = $this->get(Client::class);
    $statusCode = null;
    $h1 = null;
    $title = null;
    $description = null;
    $body = null;

    if (!$url) {
        return $response->write('Page not found')->withStatus(404);
    }

    try {
        /** @var \GuzzleHttp\Psr7\Response $res */
        $res = $client->request('GET', $url->name);
        $statusCode = $res->getStatusCode();
        $body = $res->getBody()->getContents();
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (ConnectException $e) {
        error_log($e->getMessage());
        $this->get('flash')->addMessage('error', "Произошла ошибка при проверке, не удалось подключиться");

        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
    } catch (ClientException $e) {
        $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
        if ($e->hasResponse()) {
            $statusCode = $e->getResponse()->getStatusCode();
        }
        error_log($e->getMessage());
    } catch (RequestException $e) {
        $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
        error_log($e->getMessage());
    }

    if ($body) {
        $document->loadHtml($body);
        $h1 = optional($document->first('h1'))->text();
        $title = optional($document->first('title'))->text();
        /** @var Element|null $element */
        $element = $document->first("//meta[contains(@name, 'description')]", Query::TYPE_XPATH);

        $description = $element?->getAttribute('content');
    }

    $urlCheck = UrlCheck::fromArray([$id, $statusCode, $h1, $title, $description, Carbon::now()]);
    $urlCheckRepository->save($urlCheck);

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
})->setName('urls.checks');

$app->get('/urls', function ($request, $response) {
    $twig = $this->get(Twig::class);
    $urlRepository = $this->get(UrlRepository::class);
    $urlCheckRepository = $this->get(UrlCheckRepository::class);
    /** @var Url[] $urls */
    $urls = $urlRepository->listUrls();

    if (!empty($urls)) {
        /** @param Collection<int, Url> $urlCollection
         * @return Collection<int, Url>|null
         */
        $urlCollection = collect($urls)->map(function (Url $url) use ($urlCheckRepository) {
            $urlChecks = $url->getUrlChecksByUrlId($urlCheckRepository);
            if (!empty($urlChecks)) {
                $url->setUrlChecks($urlChecks);
            }
            return $url;
        });
        $urls = $urlCollection->toArray();
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
