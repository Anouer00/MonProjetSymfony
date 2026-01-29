<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * récupère les livres avec pagination et filtre de recherche optionnel
     * @param int $page numéro de la page
     * @param int $limit nombre d'éléments par page
     * @param string|null $search terme de recherche (titre ou auteur)
     * @return Paginator
     */
    public function findAllWithPagination(int $page, int $limit, ?string $search = null): Paginator
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC');

        // filtre de recherche optionnel
        if ($search) {
            $qb->andWhere('b.title LIKE :search OR b.author LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // configuration de la pagination
        $query = $qb->getQuery();
        
        // calcul de l'offset (ex : page 2, limit 10 = début au 10ème résultat)
        $query->setFirstResult(($page - 1) * $limit)
              ->setMaxResults($limit);

        return new Paginator($query);
    }
}