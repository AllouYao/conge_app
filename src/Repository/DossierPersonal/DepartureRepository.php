<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Departure;
use App\Entity\DossierPersonal\Personal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Departure>
 *
 * @method Departure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Departure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Departure[]    findAll()
 * @method Departure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepartureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Departure::class);
    }

    /**
     * @param int $month
     * @param int $year
     * @return Departure[]
     */
    public function getDepartureByDate(int $month, int $year): array
    {
        return $this->createQueryBuilder('departure')
            ->join('departure.personal', 'personal')
            ->andWhere('YEAR(departure.date) = :year')
            ->andWhere('MONTH(departure.date) = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('departure.date', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findDeparturesByPersonal($personal): ?Departure
    {
        return $this->createQueryBuilder('d')
            ->where('d.personal = :personal')
            ->setParameter('personal', $personal)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
