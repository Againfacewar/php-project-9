<?php

namespace Hexlet\Code;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

use function DI\string;

class Connection
{
    public function __construct(
        private readonly string $host,
        private readonly string $dbName,
        private readonly string $user,
        private readonly string $password,
        private readonly int $port,
        private readonly string $scheme = 'pgsql'
    ) {
    }

    private function buildDsn(): string
    {
        return "{$this->scheme}:host={$this->host};port={$this->port};dbname={$this->dbName}";
    }

    public function connect(): ?\PDO
    {
        $dsn = $this->buildDsn();
        $conn = new \PDO($dsn, $this->user, $this->password);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $conn;
    }

    public static function createFromUrl(string $url): Connection
    {
        $databaseUrl = parse_url($url);
        $user = $databaseUrl['user'];
        $password = $databaseUrl['pass'];
        $host = $databaseUrl['host'];
        $port = $databaseUrl['port'] ?? 5432;
        $dbName = ltrim($databaseUrl['path'], '/');

        return new self($host, $dbName, $user, $password, $port);
    }
}
