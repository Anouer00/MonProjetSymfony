<?php

namespace App\Service;

use App\DTO\BookOutputDTO;
use App\Entity\Book;

/**
 * Service pour découpler la transformation des données (Responsabilité unique)
 */
class BookMapper
{
    public function mapToDto(Book $book): BookOutputDTO
    {
        $dto = new BookOutputDTO();
        $dto->id = $book->getId();
        $dto->title = $book->getTitle();
        $dto->author = $book->getAuthor();
        $dto->isbn = $book->getIsbn();
        $dto->publishedAt = $book->getPublishedAt();
        $dto->description = $book->getDescription();
        $dto->createdAt = $book->getCreatedAt();
        $dto->updatedAt = $book->getUpdatedAt();

        return $dto;
    }
}