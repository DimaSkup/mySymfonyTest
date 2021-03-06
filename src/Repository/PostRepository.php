<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */


class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function searchByQuery(string $query)
    {
        return $this->createQueryBuilder('post')
                    ->where('post.text LIKE :query')
                    ->setParameter('query', '%'.$query.'%')
                    ->getQuery()
                    ->getResult();
    }

    public function searchById(string $id)
    {
        return $this->createQueryBuilder('post')
                    ->where('post.id LIKE :id')
                    ->setParameter('id', $id)
                    ->getQuery()
                    ->getResult();
    }

    public function findAllPaginated(&$num_pages, $startPage = 0, $resultPerPage = 5)
    {
        $start = ($startPage - 1) * $resultPerPage;
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT COUNT(p.id) FROM App\Entity\Post p');
        $rows_num = $query->getSingleScalarResult();
        $num_pages = ceil($rows_num / $resultPerPage);

        return $this->findBy(
            ['is_moderated' => true],
            ['created_at' => 'DESC'],
            $resultPerPage,
            $start
        );
    }


    // /**
    //  * @return Post[] Returns an array of Post objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Post
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}