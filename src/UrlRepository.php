<?php

namespace Hexlet\Code;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function listUrls(): array
    {
        $sql = 'SELECT * FROM urls';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $urls = [];

        if ($row = $stmt->fetch()) {
            $url = Url::fromArray([$row['name'], $row['createdAt']]);
            $url->setId($row['id']);
            $urls[] = $url;
        }

        return $urls;
    }
    public function find(int $id): ?Url
    {
        $sql = 'SELECT * FROM urls WHERE id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch())  {
            $url = Url::fromArray([$row['name'], $row['createdAt']]);
            $url->setId($row['id']);
            return $url;
        }

        return null;
    }

    public function save(Url $url): void
    {
        if ($url->exists()) {
            $this->update($url);
        } else {
            $this->create($url);
        }
    }

    private function update(Url $url): void
    {
        $sql = "UPDATE urls SET name = :name WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $id = $url->getId();
        $name = $url->getName();

        $stmt->execute(['name' => $name, 'id' => $id]);
    }

    private function create(Url $url): void
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getName();
        $createdAt = $url->getCreatedAt();
        $stmt->execute(['name' => $name, 'created_at' => $createdAt]);
        $id = (int) $this->conn->lastInsertId();
        $url->setId($id);
    }
}