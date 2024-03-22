<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Absence;
use App\Entity\DossierPersonal\Personal;
use App\Utils\Status;
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

    public function getAbsenceByMonths(int $month, int $year): ?array
    {
        return $this->createQueryBuilder('abs')
            ->join('abs.personal', 'p')
            ->join('p.contract', 'contract')
            ->andWhere('YEAR(abs.startedDate) = :year')
            ->andWhere('MONTH(abs.startedDate) = :month')
            ->andWhere('p.active = true')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('abs.startedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getAbsenceByMonthsByEmployeRole(int $month, int $year): ?array
    {
        return $this->createQueryBuilder('abs')
            ->join('abs.personal', 'personal')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'categorySalarie')
            ->where('categorySalarie.name IN (:name)')
            ->andWhere('YEAR(abs.startedDate) = :year')
            ->andWhere('MONTH(abs.startedDate) = :month')
            ->andWhere('personal.active = true')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('name', [Status::OUVRIER_EMPLOYE, Status::CHAUFFEUR])
            ->orderBy('abs.startedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getAbsenceByMonthByEmployeRole(?Personal $personal, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('abs')
            ->join('abs.personal', 'personal')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'categorySalarie')
            ->where('categorySalarie.code = :code_employe OR   categorySalarie.code = :code_chauffeur')
            ->andWhere('YEAR(abs.startedDate) = :year')
            ->andWhere('MONTH(abs.startedDate) = :month')
            ->andWhere('abs.justified = :justified')
            ->andWhere('abs.personal = :personal')
            ->andWhere('personal.active = true')
            ->setParameter('code_employe', 'OE')
            ->setParameter('code_chauffeur', 'CH')
            ->setParameter('personal', $personal)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('justified', false)
            ->orderBy('abs.startedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

}