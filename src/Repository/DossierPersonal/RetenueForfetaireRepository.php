<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\RetenueForfetaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RetenueForfetaire>
 *
 * @method RetenueForfetaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method RetenueForfetaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method RetenueForfetaire[]    findAll()
 * @method RetenueForfetaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RetenueForfetaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RetenueForfetaire::class);
    }

//    /**
//     * @return retenueForfetaire[] Returns an array of retenueForfetaire objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?retenueForfetaire
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
