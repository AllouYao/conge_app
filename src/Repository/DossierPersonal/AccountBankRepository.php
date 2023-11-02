<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\AccountBank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountBank>
 *
 * @method AccountBank|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountBank|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountBank[]    findAll()
 * @method AccountBank[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountBankRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountBank::class);
    }

//    /**
//     * @return AccountBank[] Returns an array of AccountBank objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AccountBank
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
