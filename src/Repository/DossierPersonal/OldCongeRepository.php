<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\OldConge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OldConge>
 *
 * @method OldConge|null find($id, $lockMode = null, $lockVersion = null)
 * @method OldConge|null findOneBy(array $criteria, array $orderBy = null)
 * @method OldConge[]    findAll()
 * @method OldConge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OldCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OldConge::class);
    }

    //    /**
    //     * @return OldConge[] Returns an array of OldConge objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function findOneByPerso($value): ?OldConge
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->where('personal.id = :value')
            ->setParameter('value', $value)
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByPersoBuilder($value): array
    {
        return $this->createQueryBuilder('o')
            ->select([
                'o.id',
                'o.dateRetour as older_retour'
            ])
            ->join('o.personal', 'personal')
            ->where('personal.id = :value')
            ->setParameter('value', $value)
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
