<?php

namespace App\Repository\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Payroll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     * @param bool $type
     * @param Personal $personal
     * @return Payroll[]|null
     */
    public function findBulletinByCampaign(bool $campagne, bool $type, Personal $personal): ?array
    {
        return $this->createQueryBuilder('pr')
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->where('c.ordinary = :status')
            ->andWhere('c.active = :active')
            ->andWhere('p.id = :personal')
            ->setParameters(['active' => $campagne, 'status' => $type, 'personal' => $personal->getId()])
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

    public function findLastPayroll(bool $value): ?Payroll
    {
        return $this->createQueryBuilder('pr')
            ->join('pr.campagne', 'campagnes')
            ->where('campagnes.ordinary = :value')
            ->setMaxResults(1)
            ->setParameter('value', $value)
            ->orderBy('pr.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
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

    /** Obtenir le cumul des salaire de la période du prémier janvier à la date de depart  */
    public function getCumulSalaries(Personal $personal, mixed $start, mixed $end): float|int|null
    {
        $dateDebut = clone $start;
        $dateFin = clone $end;
        $query = $this->createQueryBuilder('pr')
            ->select('SUM(pr.brutAmount + pr.AncienneteAmount + pr.majorationAmount - pr.salaryTransport) as cumulSalaries')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->where('pr.personal = :personal')
            ->andWhere('campagnes.startedAt BETWEEN :dateDebut AND :dateFin')
            ->setParameter('personal', $personal)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery();
        $result = $query->getSingleResult();
        return $result['cumulSalaries'];
    }

    /** Obtenir le cumul des salaire de la periode des 12 mois partant du premier mois de l'annee précédente jusqu'à la date de depart en conges */
    public function getPeriodiqueSalary1(Personal $personal, mixed $start): float|int|null
    {
        $dateDepart = $start;
        $lastTwelveMonths = [];
        for ($i = 1; $i <= 12; $i++) {
            $date = clone $dateDepart;
            $date->modify("-$i months");
            $lastTwelveMonths[] = $date;
        }
        return $this->createQueryBuilder('pr')
            ->select('SUM(pr.brutAmount + pr.AncienneteAmount + pr.majorationAmount - pr.salaryTransport) as amount_moyen')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->leftJoin('personal.departures', 'departures')
            ->where('pr.personal = :pr_personal')
            ->andWhere('campagnes.startedAt BETWEEN :start AND :end')
            ->setParameter('pr_personal', $personal)
            ->setParameter('start', $lastTwelveMonths[11])
            ->setParameter('end', $lastTwelveMonths[0])
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Obtenir le cumul des salaire de la periode des 12 mois partant de la date du mois précédent de congés jusqu'à la date de depart de congés */
    public function getPeriodiqueSalary2(Personal $personal, mixed $start): float|int|null
    {
        $dateDepart = $start;
        $lastTwelveMonths = [];
        for ($i = 1; $i <= 11; $i++) {
            $date = clone $dateDepart;
            $date->modify("+$i months");
            $lastTwelveMonths[] = $date;
        }
        return $this->createQueryBuilder('pr')
            ->select('SUM(pr.brutAmount + pr.AncienneteAmount + pr.majorationAmount - pr.salaryTransport) as amount_moyen')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->leftJoin('personal.departures', 'departures')
            ->where('pr.personal = :pr_personal')
            ->andWhere('campagnes.startedAt BETWEEN :start AND :end')
            ->setParameter('pr_personal', $personal)
            ->setParameter('start', $lastTwelveMonths[0])
            ->setParameter('end', $lastTwelveMonths[10])
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Determiner le dernier salaire payer au salarie */
    public function getLastAmountMensuel(Personal $personal): float|int|null
    {
        $query = $this->createQueryBuilder('pr')
            ->select('(pr.brutAmount + pr.AncienneteAmount + pr.majorationAmount - pr.salaryTransport) as cumulSalaries')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->where('pr.personal = :personal')
            ->andWhere('campagnes.ordinary = true')
            ->orderBy('campagnes.startedAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->getQuery();
        $result = $query->getSingleResult();
        return $result['cumulSalaries'];
    }

    /** Determiner le salaire global des 12 dernier mois  */
    public function getSalaireGlobal(Personal $personal, mixed $start): float|int|null
    {
        $dateDepart = $start;
        $lastTwelveMonths = [];
        for ($i = 1; $i <= 12; $i++) {
            $date = clone $dateDepart;
            $date->modify("-$i months");
            $lastTwelveMonths[] = $date;
        }
        return $this->createQueryBuilder('pr')
            ->select('SUM(pr.brutAmount + pr.AncienneteAmount + pr.majorationAmount - pr.salaryTransport) as amount_moyen')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->leftJoin('personal.departures', 'departures')
            ->leftJoin('personal.salary', 'salary')
            ->where('pr.personal = :pr_personal')
            ->andWhere('campagnes.startedAt BETWEEN :start AND :end')
            ->setParameter('pr_personal', $personal)
            ->setParameter('start', $lastTwelveMonths[11])
            ->setParameter('end', $lastTwelveMonths[0])
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Determiner le salaire net du salarié */
    public function getAmountNetPayer(Personal $personal): float|int|null
    {
        $query = $this->createQueryBuilder('pr')
            ->select('(pr.netPayer) as netPayer')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->where('pr.personal = :personal')
            ->andWhere('campagnes.ordinary = true')
            ->andWhere('campagnes.active = true')
            ->orderBy('campagnes.startedAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->getQuery();
        $result = $query->getSingleResult();
        return $result['netPayer'];
    }

}