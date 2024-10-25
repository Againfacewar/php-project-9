<?php

namespace Hexlet\Code;

class UrlCheck
{
    private ?int $id = null;


    public function __construct(
        public readonly int $urlId,
        public readonly string $createdAt,
        public readonly ?string $statusCode = null,
        public readonly ?string $h1 = null,
        public readonly ?string $title = null,
        public readonly ?string $description = null
    ) {
    }

    public static function fromArray(array $data): UrlCheck
    {
        [$urlId, $statusCode, $h1, $title, $description, $createdAt] = $data;

        return new self($urlId, $createdAt, $statusCode, $h1, $title, $description);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
