<?php

namespace Hexlet\Code;

class UrlCheckRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function find(int $id): ?UrlCheck
    {
        $sql = 'SELECT * FROM url_checks WHERE id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch()) {
            $url = UrlCheck::fromArray(
                [
                    $row['url_id'],
                    $row['status_code'],
                    $row['h1'],
                    $row['title'],
                    $row['description'],
                    $row['created_at']
                ]
            );
            $url->setId($row['id']);

            return $url;
        }

        return null;
    }

    public function save(UrlCheck $urlCheck): void
    {
        if ($urlCheck->exists()) {
            $this->update($urlCheck);
        } else {
            $this->create($urlCheck);
        }
    }

    private function create(UrlCheck $urlCheck): void
    {
        $sql = "INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at)
            VALUES (:urlId, :statusCode, :h1, :title, :description, :createdAt)";
        $stmt = $this->conn->prepare($sql);
        $urlId = $urlCheck->getUrlId();
        $statusCode = $urlCheck->getStatusCode();
        $h1 = $urlCheck->getH1();
        $title = $urlCheck->getTitle();
        $desc = $urlCheck->getDescription();
        $createdAt = $urlCheck->getCreatedAt();
        $stmt->execute(
            [
                'urlId' => $urlId,
                'statusCode' => $statusCode,
                'h1' => $h1,
                'title' => $title,
                'description' => $desc,
                'createdAt' => $createdAt
            ]
        );
        $id = (int) $this->conn->lastInsertId();

        $urlCheck->setId($id);
    }

    public function getUrlChecksByUrlId(int $urlId): array
    {
        $sql = 'SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$urlId]);
        $urlChecks = [];

        while ($row = $stmt->fetch()) {
            $urlCheck = UrlCheck::fromArray(
                [
                    $row['url_id'],
                    $row['status_code'],
                    $row['h1'],
                    $row['title'],
                    $row['description'],
                    $row['created_at']
                ]
            );

            $urlCheck->setId($row['id']);
            $urlChecks[] = $urlCheck;
        }

        return $urlChecks;
    }
}
