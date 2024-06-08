<?php

namespace App\Repository;

use App\Entity\Conge;
use App\Utils\Status;
use App\Entity\Personal;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Conge>
 *
 * @method Conge|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conge|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conge[]    findAll()
 * @method Conge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conge::class);
    }

    /**
     * @return Conge[]
     */
    public function findConge(): array
    {
        return $this->createQueryBuilder('co')
            ->select([
                'p.firstName as nom',
                'p.lastName as prenoms',
                'co.dateRetour as retour',
                'co.dateDepart as depart',
                'co.isConge as en_conge',
                'co.dateDernierRetour as dernier_retour',
                'co.uuid',
                'co.totalDays',
                'co.dateReprise',
            ])
            ->join('co.personal', 'p')
            ->getQuery()
            ->getResult();


    }

    public function findCongesBuilder(int $personal, bool $active): array
    {
        return $this->createQueryBuilder('co')
            ->select([
                'personal.firstName as nom',
                'personal.lastName as prenoms',
                'co.dateRetour as retour',
                'co.dateDepart as depart',
                'co.isConge as en_conge',
                'co.dateDernierRetour as dernier_retour',
                'co.uuid',
                'co.totalDays',
            ])
            ->join('co.personal', 'personal')
            ->where('personal.id = :personal')
            ->andWhere('co.isConge = :value')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->setParameter('value', $active)
            ->orderBy('co.id', 'DESC')
            ->getQuery()->getResult();

    }

    public function getLastCongeByID(int $personal, bool $active): ?Conge
    {
        return $this->createQueryBuilder('co')
            ->join('co.personal', 'personal')
            ->where('personal.id = :personal')
            ->andWhere('co.isConge = :value')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->setParameter('value', $active)
            ->orderBy('co.id', 'DESC')
            ->getQuery()->getOneOrNullResult();
    }
    public function getLastCongeIncomplete(Personal $personal): ?Conge
    {
        return $this->createQueryBuilder('co')
            ->join('co.personal', 'personal')
            ->where('personal = :personal')
            ->andWhere('co.oneMonth = false')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function active(Personal $personal): ?Conge
    {
        return $this->createQueryBuilder('co')
            ->where('co.personal = :personal')
            ->andWhere("co.isConge = true")
            ->setParameter('personal', $personal)
            ->setMaxResults(1)
            ->orderBy('co.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function activeForAll(): ?array
    {
        return $this->createQueryBuilder('co')
            ->join('co.personal', 'personal')
            ->andWhere("co.isConge = true")
            ->orderBy('co.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Obtenir les 15 jours qui précède la date de depart en congé */
    public function get15DaysBeforeDate(\DateTime $date)
    {
        $startDate = clone $date;
        $startDate->modify('-15 days');

        $endDate = clone $date;

        return $this->createQueryBuilder('c')
            ->where('c.dateDepart >= :startDate')
            ->andWhere('c.dateDepart < :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    /** Obtenir les 15 jours qui suivent la date de retour en congé */
    public function get15DaysAfterDate(\DateTime $date)
    {
        $startDate = clone $date;

        $endDate = clone $date;
        $endDate->modify('+15 days');

        return $this->createQueryBuilder('c')
            ->where('c.dateDernierRetour > :startDate')
            ->andWhere('c.dateDernierRetour <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function findCongeByPersonal(int $personal): ?Conge
    {
        return $this->createQueryBuilder('co')
            ->join('co.personal', 'personal')
            ->where('personal.id = :personal')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->orderBy('co.id', 'DESC')
            ->getQuery()->getOneOrNullResult();
    }

    public function findCongeByPeriode(mixed $start, mixed $end, int $personal_id): ?Conge
    {
        return $this->createQueryBuilder('co')
            ->join('co.personal', 'personal')
            ->where('personal.id = :personal_id')
            ->andWhere('co.dateDepart >= :startDate')
            ->andWhere('co.dateDepart <= :endDate')
            ->andWhere('co.status IN (:status)')
            ->setParameter('personal_id', $personal_id)
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end)
            ->setMaxResults(1)
            ->setParameter('status', [Status::PAYE, Status::IMPAYEE])
            ->orderBy('co.id', 'DESC')
            ->getQuery()->getOneOrNullResult();
    }
}
