<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Expr\Array_;

/**
 * @extends ServiceEntityRepository<Personal>
 *
 * @method Personal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Personal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Personal[]    findAll()
 * @method Personal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersonalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personal::class);
    }

//    /**
//     * @return Personal[] Returns an array of Personal objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Personal
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findAllPersonal(): array
    {
        $qb = $this->createQueryBuilder('p')->getQuery()->getResult();
        return array_map(function ($result) {
            return $result;
        }, $qb);
    }
}
