<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Absence;
use App\Entity\DossierPersonal\Personal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Absence>
 *
 * @method Absence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Absence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Absence[]    findAll()
 * @method Absence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Absence::class);
    }

    //    /**
    //     * @return Absence[] Returns an array of Absence objects
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

    //    public function findOneBySomeField($value): ?Absence
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @param Personal|null $personal
     * @param int $month
     * @param int $year
     * @return Absence[]|null Returns an array of Absence objects
     */
    public function getAbsenceByMonth(?Personal $personal, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('abs')
            ->andWhere('abs.personal = :personal')
            ->andWhere('YEAR(abs.startedDate) = :year')
            ->andWhere('MONTH(abs.startedDate) = :month')
            ->andWhere('abs.justified = :justified')
            ->setParameter('personal', $personal)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('justified', false)
            ->orderBy('abs.startedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}