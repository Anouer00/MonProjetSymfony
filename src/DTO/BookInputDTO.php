<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BookInputDTO
{
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank(message: "L'auteur est obligatoire")]
    #[Assert\Length(max: 255)]
    public ?string $author = null;

    #[Assert\NotBlank(message: "L'ISBN est obligatoire")]
    #[Assert\Length(max: 20)]
    public ?string $isbn = null;

    #[Assert\NotNull(message: "La date de publication est obligatoire")]
    public ?\DateTimeImmutable $publishedAt = null;

    public ?string $description = null;
}