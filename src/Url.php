<?php

namespace Hexlet\Code;

use Carbon\Carbon;

class Url
{
    public function __construct(
        public readonly string $name,
        public readonly string $created_at,
        private ?int $id = null,
        private ?array $urlsCheck = []
    ) {
    }

    public static function fromArray(array $data): Url
    {
        [$name, $createdAt] = $data;

        return new Url($name, $createdAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getUrlChecks(): ?array
    {
        return $this->urlsCheck;
    }

    public function getLastUrlCheck(): ?UrlCheck
    {
        return $this->urlsCheck[0] ?? null;
    }

    public function setUrlChecks(?array $urlChecks): void
    {
        $this->urlsCheck = $urlChecks;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }

    public function getUrlChecksByUrlId(UrlCheckRepository $urlCheckRepo): array
    {
        return $urlCheckRepo->getUrlChecksByUrlId($this->id);
    }

    public static function normalizeUrl(string $url): string
    {
        $parsedUrl = parse_url(trim($url));

        return "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
    }
}
