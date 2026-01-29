<?php

namespace App\DTO;

class BookOutputDTO
{
    public ?int $id = null;
    public ?string $title = null;
    public ?string $author = null;
    public ?string $isbn = null;
    public ?\DateTimeImmutable $publishedAt = null;
    public ?string $description = null;
    public ?\DateTimeImmutable $createdAt = null;
    public ?\DateTimeImmutable $updatedAt = null;
}