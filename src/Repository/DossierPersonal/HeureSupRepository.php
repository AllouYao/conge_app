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

    /**
     * @param Personal $personal
     * @param int $month
     * @param int $year
     * @return HeureSup[]
     */
    public function getHeureSupByDate(Personal $personal, int $month, int $year): array
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

    public function getNbHeursSupp(Personal $personal, int $month, int $year): ?HeureSup
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.personal = :personal')
            ->andWhere('YEAR(h.startedDate) = :year')
            ->andWhere('MONTH(h.startedDate) = :month')
            ->setParameter('personal', $personal)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getOneOrNullResult();
    }


}