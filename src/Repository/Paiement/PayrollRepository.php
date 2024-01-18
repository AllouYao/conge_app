<?php

namespace App\Repository\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Payroll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Payroll>
 *
 * @method Payroll|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payroll|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payroll[]    findAll()
 * @method Payroll[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PayrollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payroll::class);
    }

    /**
     * @param bool $campagne
     * @param Personal $personal
     * @return Payroll[]|null
     */
    public function findBulletinByCampaign(bool $campagne, Personal $personal): ?array
    {
        return $this->createQueryBuilder('pr')
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->andWhere('c.active = :active')
            ->andWhere('p.id = :personal')
            ->setParameters(['active' => $campagne, 'personal' => $personal->getId()])
            ->getQuery()->getResult();
    }

    /**
     * @param bool $campagne
     * @return Payroll[]|null
     */
    public function findPayrollByCampaign(bool $campagne): ?array
    {
        return $this->createQueryBuilder('pr')
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->andWhere('c.active = :active')
            ->setParameter('active', $campagne)
            ->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getTotalSalarie(Personal $personal, mixed $start, mixed $end): float|int|null
    {
        return $this->createQueryBuilder('pr')
            ->select('SUM((pr.brutAmount - 30000)) as amount_moyen')
            ->join('pr.personal', 'personal')
            ->leftJoin('personal.salary', 'salary')
            ->where('pr.personal = :pr_personal')
            ->andWhere('pr.createdAt >= :start_date')
            ->andWhere('pr.createdAt <= :end_date')
            ->setParameter('pr_personal', $personal)
            ->setParameter('start_date', $start)
            ->setParameter('end_date', $end)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalSalarieBaseAndSursalaire(Personal $personal, mixed $start, mixed $end): float|int|null
    {
        return $this->createQueryBuilder('pr')
            ->select('SUM((pr.baseAmount + pr.sursalaire)) as amount_moyen')
            ->join('pr.personal', 'personal')
            ->leftJoin('personal.salary', 'salary')
            ->where('pr.personal = :pr_personal')
            ->andWhere('pr.createdAt >= :start_date')
            ->andWhere('pr.createdAt <= :end_date')
            ->setParameter('pr_personal', $personal)
            ->setParameter('start_date', $start)
            ->setParameter('end_date', $end)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findEtatSalaire(mixed $mouth1, mixed $mouth2, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'personal.id as personal_id',
                'personal.firstName',
                'personal.lastName',
                'personal.matricule',
                'personal.refCNPS',
                'YEAR(personal.birthday) as personal_birthday',
                'contract.dateEmbauche as embauche',
                'salary.totalPrimeJuridique as prime_juridique',
                'salary.primeLogement as aventage_nature_imposable',
                'contract.dateEmbauche',
                'payroll.baseAmount',
                'payroll.brutAmount',
                'payroll.salaryCnps',
                'payroll.imposableAmount',
                'payroll.salaryIts',
                'payroll.salaryCmu',
                'payroll.salarySante',
                'payroll.numberPart',
                'payroll.createdAt',
                'campagnes.startedAt'
            ])
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->leftJoin('personal.salary', 'salary')
            ->leftJoin('personal.contract', 'contract')
            ->where('campagnes.active = false')
            ->andWhere('payroll.createdAt BETWEEN ?1 and ?2');
        $qb->setParameters(['1' => $mouth1, '2' => $mouth2]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findEtatSalaireCurrentMonth(bool $campagne, mixed $currentFullDate): array
    {
        $currentMonth = $currentFullDate->format('m');
        $currentYear = $currentFullDate->format('Y');

        return $this->createQueryBuilder('payroll')
            ->select([
                'personal.id as personal_id',
                'personal.firstName',
                'personal.lastName',
                'personal.matricule',
                'personal.refCNPS',
                'YEAR(personal.birthday) as personal_birthday',
                'contract.dateEmbauche as embauche',
                'salary.totalPrimeJuridique as prime_juridique',
                'salary.primeLogement as aventage_nature_imposable',
                'contract.dateEmbauche',
                'payroll.baseAmount',
                'payroll.brutAmount',
                'payroll.salaryCnps',
                'payroll.imposableAmount',
                'payroll.salaryIts',
                'payroll.salaryCmu',
                'payroll.salarySante',
                'payroll.numberPart',
                'payroll.createdAt'
            ])
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->leftJoin('personal.salary', 'salary')
            ->leftJoin('personal.contract', 'contract')
            ->where('campagnes.active = :active')
            ->andWhere('MONTH(payroll.createdAt) = :currentMonth')
            ->andWhere('YEAR(payroll.createdAt) = :currentYear')
            ->setParameters([
                'currentMonth' => $currentMonth,
                'currentYear' => $currentYear,
                'active' => $campagne
            ])
            ->getQuery()->getResult();
    }

    public function findCnps(): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'personal.id as personal_id',
                'personal.firstName',
                'personal.lastName',
                'personal.matricule',
                'personal.refCNPS',
                'YEAR(personal.birthday) as personal_birthday',
                'contract.dateEmbauche as embauche',
                'payroll.imposableAmount',
            ])
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->leftJoin('personal.contract', 'contract')
            ->where('campagnes.active = false');
        return $qb->getQuery()->getResult();
    }
    public function findSalarialeCampagne(bool $campagne, mixed $years, mixed $month): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'personal.id as personal_id',
                'personal.firstName as nom',
                'personal.lastName as prenoms',
                'personal.refCNPS',
                'personal.older',
                'YEAR(personal.birthday) as personal_birthday',
                'payroll.matricule',
                'payroll.baseAmount',
                'payroll.AncienneteAmount',
                'payroll.primeFonctionAmount',
                'payroll.primeLogementAmount',
                'payroll.indemniteFonctionAmount',
                'payroll.indemniteLogementAmount',
                'payroll.majorationAmount',
                'payroll.congesPayesAmount',
                'payroll.brutAmount',
                'payroll.salaryCnps',
                'payroll.imposableAmount',
                'payroll.salaryIts',
                'payroll.salaryCmu',
                'payroll.netPayer',
                'payroll.employeurCr',
                'payroll.employeurIs',
                'payroll.amountTA',
                'payroll.amountFPC',
                'payroll.employeurAt',
                'payroll.employeurPf',
                'payroll.salaryTransport',
                'payroll.amountPrimePanier',
                'payroll.amountPrimeSalissure',
                'payroll.amountPrimeOutillage',
                'payroll.amountPrimeTenueTrav',
                'payroll.amountPrimeRendement',
                'payroll.amountPrimeRendement',
                'payroll.aventageNonImposable',
                'payroll.numberPart',
                'payroll.numCnps',
                'payroll.dateEmbauche',
                'payroll.createdAt',
                'campagnes.startedAt'
            ])
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->where('campagnes.active = :active')
            ->andWhere('YEAR(payroll.createdAt) = :year')
            ->andWhere('MONTH(payroll.createdAt) = :month');
        $qb
            ->setParameter('active', $campagne)
            ->setParameter('year', $years)
            ->setParameter('month', $month);
        return $qb->getQuery()->getResult();
    }

}