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

    /** Obtenir le depart qui est entre les 15 jours qui suivent la date de retour en congé */
    public function get15DaysAfterDate(\DateTime $date): ?Departure
    {
        $startDate = clone $date;

        $endDate = clone $date;
        $endDate->modify('+15 days');

        return $this->createQueryBuilder('d')
            ->where('c.dateDernierRetour > :startDate')
            ->andWhere('c.dateDernierRetour <= :endDate')
            ->andWhere('d.date BETWEEN :')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function get15DaysAftersDate(mixed $date, Personal $personal): ?Departure
    {
        $startDate = $date;
        $nextFifteenDays = [];
        for ($i = 1; $i <= 15; $i++) {
            $date = clone $startDate;
            $date->modify("+$i days");
            $nextFifteenDays[] = $date;
        }
        return $this->createQueryBuilder('d')
            ->join('d.personal', 'personal')
            ->join('personal.conges', 'c')
            ->where('d.personal = :d_personal')
            ->andWhere('d.date BETWEEN :start AND :end')
            ->setParameter('d_personal', $personal)
            ->setParameter('start', $nextFifteenDays[14])
            ->setParameter('end', $nextFifteenDays[0])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDeparture(): ?Departure
    {
        return $this->createQueryBuilder('departure')
            ->getQuery()->getOneOrNullResult();
    }

}
