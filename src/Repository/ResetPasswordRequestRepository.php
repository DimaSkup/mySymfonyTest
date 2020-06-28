<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\ResetPasswordRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ResetPasswordRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ResetPasswordRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method ResetPasswordRequest[]    findAll()
 * @method ResetPasswordRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }


    public function loadTokenByEmail(string $email)
    {
        return $this->createQueryBuilder('res')
            ->where('res.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getResult();
    }

    public function validateTokenAndFetchUser(ResetPasswordRequest $token)
    {
        $userEmail = $token->getEmail();

        return $this->getEntityManager()->getRepository(User::class)->loadUserByUsername($userEmail);
    }

    public function deleteExpiredToken()
    {
        /**
        $expiredToken = $this->createQueryBuilder('res')
             ->where('res.expires_at < :now')
             ->setParameter('now', new \DateTimeImmutable('now'))
             ->getQuery()
             ->getArrayResult();

        foreach($expiredToken as $token)
        $this->getEntityManager()->remove($token);
         */
    }
}
