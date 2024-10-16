<?php

namespace Hexlet\Code;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

class Connection
{
    private static function buildDsn(string $scheme, string $host, string $port, string $dbName): string
    {
        return "$scheme:host=$host;port=$port;dbname=$dbName";
    }

    public static function connect(string $dbUrl): ?\PDO
    {
        $dbName = '';
        $host = '';
        $port = 5432;
        $scheme = 'pgsql';
        $user = '';
        $password = '';
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
            $user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME');
            $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
            $dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');
        } catch (InvalidPathException $e) {
            error_log($e->getMessage());
        }

        if ($dbUrl) {
            $databaseUrl = parse_url($dbUrl);
            $user = $databaseUrl['user'];
            $password = $databaseUrl['pass'];
            $host = $databaseUrl['host'];
            $port = $databaseUrl['port'] ?? 5432;
            $dbName = ltrim($databaseUrl['path'], '/');
        }
        $dsn = self::buildDsn($scheme, $host, $port, $dbName);
        $conn = new \PDO($dsn, $user, $password);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $conn;
    }
}
