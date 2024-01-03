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

    public function findPayrollByCampaign(bool $campagne): array
    {
        return $this->createQueryBuilder('pr')
            ->select([
                'pr.id',
                'p.firstName as first_name',
                'p.id as personalId',
                'p.lastName as last_name',
                'cy.intitule as categories_name',
                'pr.numberPart as nombre_part',
                'pr.baseAmount as base_salary',
                'pr.sursalaire as sursalaire',
                'pr.brutAmount as brut_salary',
                'pr.imposableAmount as imposable_salary',
                'pr.salaryIts as salary_its',
                'pr.salaryCnps as salary_cnps',
                'pr.salaryCmu as salary_cmu',
                'pr.salarySante as salary_assurance',
                'pr.salaryTransport as salary_transport',
                'pr.fixcalAmount as montant_fixcal_salary',
                'pr.netPayer as net_payer',
                'pr.employeurIs as employeur_is',
                'pr.employeurFdfp as employeur_fdfp',
                'pr.employeurCmu as employeur_cmu',
                'pr.employeurPf as employeur_pf',
                'pr.employeurAt as employeur_at',
                'pr.employeurCnps as employeur_cnps',
                'pr.employeurCr as employeur_cr',
                'pr.employeurSante as employeur_assurance',
                'pr.fixcalAmountEmployeur as fixcal_amount_employeur',
                'pr.masseSalary as masse_salary',
                'c.startedAt as debut'
            ])
            ->join('pr.personal', 'p')
            ->join('p.categorie', 'cy')
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
                'payroll.createdAt'
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

    public function findBulletinByCampaign(bool $campagne, Personal $personal): array
    {
        return $this->createQueryBuilder('pr')
            ->select([
                'p.id as personal_id',
                'p.firstName as first_name',
                'p.lastName as last_name',
                'p.matricule as personal_matricule',
                'p.refCNPS as numero_cnps',
                'p.etatCivil as personal_etat_civil',
                'contract.dateEmbauche as date_embauche',
                'conges.dateDernierRetour as dernier_retour_conge',
                'conges.dateDepart as date_depart',
                'conges.dateRetour as date_retour',
                'cy.intitule as categories_name',
                'categories_salarie.code as categories_code',
                'salary.totalPrimeJuridique as prime_juridique',
                'salary.primeLogement as prime_logement',
                'charge_personals.amountIts as personal_its',
                'charge_personals.AmountTotalChargePersonal as total_revenu_divers',
                'pr.id',
                'pr.numberPart as nombre_part',
                'pr.baseAmount as base_salary',
                'pr.sursalaire as sursalaire',
                'pr.brutAmount as brut_salary',
                'pr.imposableAmount as imposable_salary',
                'pr.salaryIts as salary_its',
                'pr.salaryCnps as salary_cnps',
                'pr.salaryCmu as salary_cmu',
                'pr.salarySante as salary_assurance',
                'pr.salaryTransport as salary_transport',
                'pr.fixcalAmount as montant_fixcal_salary',
                'pr.netPayer as net_payer',
                'pr.employeurIs as employeur_is',
                'pr.employeurFdfp as employeur_fdfp',
                'pr.employeurCmu as employeur_cmu',
                'pr.employeurPf as employeur_pf',
                'pr.employeurAt as employeur_at',
                'pr.employeurCnps as employeur_cnps',
                'pr.employeurCr as employeur_cr',
                'pr.employeurSante as employeur_assurance',
                'pr.fixcalAmountEmployeur as fixcal_amount_employeur',
                'pr.masseSalary as masse_salary',
                'c.startedAt as debut',
                'c.closedAt as fin'
            ])
            ->join('pr.personal', 'p')
            ->join('p.chargePersonals', 'charge_personals')
            ->leftJoin('p.conges', 'conges')
            ->join('p.categorie', 'cy')
            ->join('cy.categorySalarie', 'categories_salarie')
            ->join('p.contract', 'contract')
            ->leftJoin('p.salary', 'salary')
            ->leftJoin('salary.avantage', 'avantage')
            ->join('pr.campagne', 'c')
            ->andWhere('c.active = :active')
            ->andWhere('p.id = :personal')
            ->setParameters(['active' => $campagne, 'personal' => $personal->getId()])
            ->getQuery()->getResult();
    }

    public function findSalarialeCampagne(bool $campagne, mixed $years, mixed $month): array
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
               // 'salary.heursupplementaire as heure_supp',
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
            ->andWhere('YEAR(payroll.createdAt) = :year')
            ->andWhere('MONTH(payroll.createdAt) = :month');
        $qb
            ->setParameter('active', $campagne)
            ->setParameter('year', $years)
            ->setParameter('month', $month);
        return $qb->getQuery()->getResult();
    }

}