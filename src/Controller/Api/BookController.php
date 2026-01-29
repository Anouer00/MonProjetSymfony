<?php

namespace App\Controller\Api;

use App\DTO\BookInputDTO;
use App\DTO\BookOutputDTO;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\BookMapper;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/books')]
#[OA\Tag(name: 'Livres (Books)')]
class BookController extends AbstractController
{
    private BookMapper $bookMapper;

    public function __construct(BookMapper $bookMapper)
    {
        $this->bookMapper = $bookMapper;
    }

    #[Route('', name: 'api_books_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste paginée des livres',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: BookOutputDTO::class))),
                new OA\Property(property: 'page', type: 'integer'),
                new OA\Property(property: 'total', type: 'integer'),
            ]
        )
    )]
    public function index(BookRepository $bookRepository, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $search = $request->query->get('q'); 

        $paginator = $bookRepository->findAllWithPagination($page, $limit, $search);
        
        $data = [];
        foreach ($paginator as $book) {
            $data[] = $this->bookMapper->mapToDto($book);
        }

        return $this->json([
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
            'total' => count($paginator),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_books_show', methods: ['GET'])]
    public function show(Book $book): JsonResponse
    {
        return $this->json($this->bookMapper->mapToDto($book), Response::HTTP_OK);
    }

    #[Route('', name: 'api_books_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER', message: 'Accès refusé. Vous devez être connecté.')]
    public function create(
        Request $request, 
        SerializerInterface $serializer, 
        ValidatorInterface $validator, 
        EntityManagerInterface $em
    ): JsonResponse {
        $inputDto = $serializer->deserialize($request->getContent(), BookInputDTO::class, 'json');

        $errors = $validator->validate($inputDto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $book = new Book();
        $book->setTitle($inputDto->title)
             ->setAuthor($inputDto->author)
             ->setIsbn($inputDto->isbn)
             ->setPublishedAt($inputDto->publishedAt)
             ->setDescription($inputDto->description);

        $em->persist($book);
        $em->flush();

        return $this->json($this->bookMapper->mapToDto($book), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_books_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(
        Book $book,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ): JsonResponse {
        $inputDto = $serializer->deserialize($request->getContent(), BookInputDTO::class, 'json');

        $errors = $validator->validate($inputDto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $book->setTitle($inputDto->title)
             ->setAuthor($inputDto->author)
             ->setIsbn($inputDto->isbn)
             ->setPublishedAt($inputDto->publishedAt)
             ->setDescription($inputDto->description);

        $em->flush();

        return $this->json($this->bookMapper->mapToDto($book), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_books_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent supprimer des livres.')]
    public function delete(Book $book, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($book);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}