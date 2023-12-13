<?php


namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\HeureSup;
use App\Entity\DossierPersonal\Personal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeureSup>
 *
 * @method HeureSup|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeureSup|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeureSup[]    findAll()
 * @method HeureSup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeureSupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeureSup::class);
    }

    //    /**
    //     * @return HeureSup[] Returns an array of HeureSup objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?HeureSup
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getHeureSupByDate(Personal $personal, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('h')
        ->andWhere('h.personal = :personal')
        ->andWhere('YEAR(h.startedDate) = :year')
        ->andWhere('MONTH(h.startedDate) = :month')
        ->setParameter('personal', $personal)
        ->setParameter('year', $year)
        ->setParameter('month', $month)
        ->orderBy('h.startedDate', 'ASC')
        ->getQuery()
        ->getResult();
    }

}