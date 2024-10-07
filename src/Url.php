<?php

namespace Hexlet\Code;

use Carbon\Carbon;

class Url
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $created_at = null;

    public static function fromArray(array $data): Url
    {
        $url = new Url();
        $url->setName($data['name']);
        $url->setCreatedAt($data['createdAt']);

        return $url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->created_at = $createdAt;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }

    public static function normalizeUrl(string $url): string
    {
        $parsedUrl = parse_url($url);

        return "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
    }
}