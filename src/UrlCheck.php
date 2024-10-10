<?php

namespace Hexlet\Code;

class UrlCheck
{
    private ?int $id = null;
    private ?int $urlId = null;
    private ?string $statusCode = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $createdAt = null;

    public static function fromArray(array $data): UrlCheck
    {
        [$urlId, $statusCode, $h1, $title, $description, $createdAt] = $data;
        $url = new self();
        $url->setUrlId($urlId);
        $url->setStatusCode($statusCode);
        $url->setH1($h1);
        $url->setTitle($title);
        $url->setDescription($description);
        $url->setCreatedAt($createdAt);

        return $url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlId(): ?string
    {
        return $this->urlId;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function setStatusCode(?string $statusCode): void
    {
        if ($statusCode) {
            $this->statusCode = $statusCode;
        }
    }

    public function setH1(?string $h1): void
    {
        if ($h1) {
            $this->h1 = $h1;
        }
    }

    public function setTitle(?string $title): void
    {
        if ($title) {
            $this->title = $title;
        }
    }

    public function setDescription(?string $description): void
    {
        if ($description) {
            $this->title = $description;
        }
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
