<?php

namespace Hexlet\Code;

class Connection
{
    public static function buildDsn(string $scheme, string $host, string $port, string $dbName): string
    {
        return "$scheme:host=$host;port=$port;dbname=$dbName";
    }
}