<?php

namespace App\Repository\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Entity\Paiement\Payroll;
use App\Utils\Status;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

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

    /*
     * Information
     */
    public function findLastCamapagne(?Campagne $campagne)
    {
        $qb = $this->createQueryBuilder('p');
        return $qb
            ->select([
                'SUM(p.masseSalary) as global_salaire_brut',
                'SUM(p.fixcalAmount + p.socialAmount) as global_charge_personal',
                'SUM(p.fixcalAmountEmployeur + p.socialAmountEmployeur) as global_charge_employeur',
                'SUM(p.salaryIts) as fiscale_salariale',
                'SUM(p.employeurIs + p.amountTA) as fiscale_patronale',
                'SUM(p.salaryCmu + p.salaryCnps) as sociale_salariale',
                'SUM(p.employeurCr + p.employeurPf + p.amountTA) as sociale_patronale',
                'count(p.id) as nombre_personal',
            ])
            ->where('p.campagne = :p_campagne')
            ->setParameter('p_campagne', $campagne->getId())
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param bool $active
     * @param Personal $personal
     * @return Payroll[]|null
     * Retourne le dictionnaire des salaire de la campagne en fonction du type et du status de la campagne pour les bulletins
     */
    public function findBulletinByCampaign(bool $active, Payroll $payroll): ?Payroll
    {
        return $this->createQueryBuilder('pr')
            ->setParameter('active', $active)
            ->setParameter('is', $payroll->getId())
            ->getQuery()->getResult();
    }

    /**
     * @param bool $active
     * @return Payroll[]|null
     * Retourne le dictionnaire des salaire de la campagne en fonction du status de la campagne pour le livre de paie
     */
    public function findPayrollByCampaign(bool $active): ?array
    {
        return $this->createQueryBuilder('pr')
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->andWhere('c.active = :active')
            ->setParameter('active', $active)
            ->getQuery()->getResult();
    }


    public function findPayrollByCampaignEmploye(bool $active): ?array
    {
        return $this->createQueryBuilder('pr')
            ->join('pr.personal', 'p')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'categorySalarie')
            ->join('pr.campagne', 'c')
            ->andWhere("categorySalarie.code IN (:code)")
            ->andWhere('c.active = :active')
            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
            ->setParameter('active', $active)
            ->getQuery()->getResult();
    }

    public function findEtatSalaireClone(array $months, int $year, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'personal.id as personal_id',
                'personal.firstName as nom',
                'personal.lastName as prenoms',
                'personal.refCNPS',
                'personal.older',
                'w.name as station',
                'personal.uuid as personal_uuid',
                'YEAR(personal.birthday) as personal_birthday',
                'payroll.id as payroll_id',
                'payroll.uuid as payroll_uuid',
                'payroll.matricule',
                'payroll.dayOfPresence',
                'payroll.baseAmount',
                'payroll.sursalaire',
                'payroll.AncienneteAmount',
                'payroll.primeFonctionAmount',
                'payroll.primeLogementAmount',
                'payroll.indemniteFonctionAmount',
                'payroll.indemniteLogementAmount',
                'payroll.majorationAmount',
                'payroll.congesPayesAmount',
                'payroll.brutAmount',
                'payroll.imposableAmount',
                'payroll.salaryCnps',
                'payroll.salaryIts',
                'payroll.fixcalAmount',
                'payroll.salaryCmu',
                'payroll.salarySante',
                'payroll.totalRetenueSalarie',
                'payroll.netPayer',
                'payroll.employeurCr',
                'payroll.employeurIs',
                'payroll.employeurCmu',
                'payroll.amountTA',
                'payroll.amountFPC',
                'payroll.employeurFdfp',
                'payroll.employeurAt',
                'payroll.employeurPf',
                'payroll.employeurSante',
                'payroll.totalRetenuePatronal',
                'payroll.masseSalary',
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
                'payroll.remboursNet',
                'payroll.remboursBrut',
                'payroll.retenueNet',
                'payroll.retenueBrut',
                'payroll.amountMensualityPret',
                'payroll.amountMensuelAcompt',
                'campagnes.startedAt',
                'campagnes.dateDebut as periode_debut',
                'campagnes.dateFin as periode_fin',
                'campagnes.ordinary'
            ])
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->leftJoin('personal.workplace', "w")
            ->where('campagnes.active = false')
            ->andWhere('campagnes.status = :status')
            ->andWhere('payroll.status = :payroll_status')
            ->andWhere('MONTH(campagnes.dateDebut) IN (?1) AND YEAR(campagnes.dateDebut) = ?2');
        $qb->setParameters([1 => $months, 2 => $year, 'status' => Status::TERMINER, 'payroll_status' => Status::PAYE]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findEtatSalaireByRoleEmployer(array $mouths, int $year, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'personal.id as personal_id',
                'personal.firstName as nom',
                'personal.lastName as prenoms',
                'personal.refCNPS',
                'personal.older',
                'personal.service as station',
                'personal.uuid as personal_uuid',
                'YEAR(personal.birthday) as personal_birthday',
                'payroll.matricule',
                'payroll.dayOfPresence',
                'payroll.baseAmount',
                'payroll.sursalaire',
                'payroll.AncienneteAmount',
                'payroll.primeFonctionAmount',
                'payroll.primeLogementAmount',
                'payroll.indemniteFonctionAmount',
                'payroll.indemniteLogementAmount',
                'payroll.majorationAmount',
                'payroll.congesPayesAmount',
                'payroll.brutAmount',
                'payroll.imposableAmount',
                'payroll.salaryCnps',
                'payroll.salaryIts',
                'payroll.fixcalAmount',
                'payroll.salaryCmu',
                'payroll.salarySante',
                'payroll.totalRetenueSalarie',
                'payroll.netPayer',
                'payroll.employeurCr',
                'payroll.employeurIs',
                'payroll.employeurCmu',
                'payroll.amountTA',
                'payroll.amountFPC',
                'payroll.employeurFdfp',
                'payroll.employeurAt',
                'payroll.employeurPf',
                'payroll.employeurSante',
                'payroll.totalRetenuePatronal',
                'payroll.masseSalary',
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
                'campagnes.startedAt',
                'campagnes.dateDebut as periode_debut',
                'campagnes.dateFin as periode_fin',
                'campagnes.ordinary'
            ])
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('campagnes.active = false')
            ->andWhere('personal.active = true')
            ->andWhere('campagnes.status = :status')
            ->andWhere('payroll.status = :payroll_status')
            ->andWhere('category_salarie.code = :code')
            ->andWhere('MONTH(campagnes.dateDebut) IN (?1) AND YEAR(campagnes.dateDebut) = ?2');
        $qb->setParameters([1 => $mouths, 2 => $year, 'status' => Status::TERMINER, 'payroll_status' => Status::PAYE, 'code' => ['OUVRIER / EMPLOYES', 'CHAUFFEURS']]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findByPeriode(mixed $mouth1, mixed $mouth2, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->where('campagnes.active = false')
            ->andWhere('campagnes.dateDebut BETWEEN ?1 AND ?2');
        $qb->setParameters(['1' => $mouth1, '2' => $mouth2]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findByPeriodeEmplyees(mixed $mouth1, mixed $mouth2, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select()
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('campagnes.active = false')
            ->andWhere('personal.active = true')
            ->andWhere('category_salarie.code = :code')
            ->andWhere('campagnes.dateDebut BETWEEN ?1 AND ?2')
            ->orderBy('personal.matricule', 'ASC');
        $qb->setParameters(['1' => $mouth1, '2' => $mouth2, 'code' => ['OUVRIER / EMPLOYES', 'CHAUFFEURS']]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findSalarialeCampagne(bool $campagne): array
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
                'personal.genre',
                'personal.etatCivil',
                'job.name as emploie',
                'payroll.id',
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
                'payroll.amountAvantageImposable',
                'payroll.aventageNonImposable',
                'payroll.numberPart',
                'payroll.numCnps',
                'payroll.dateEmbauche',
                'payroll.createdAt',
                'payroll.dayOfPresence as day_work',
                'campagnes.startedAt',
                'campagnes.dateDebut as periode_debut',
                'COUNT(chargePeople.id) as nb_enfant'
            ])
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->join('personal.job', 'job')
            ->leftJoin('personal.chargePeople', 'chargePeople')
            ->where('campagnes.active = :active')
            ->groupBy('campagnes.dateDebut', 'personal.lastName', 'payroll.id');
        $qb
            ->setParameter('active', $campagne);
        return $qb->getQuery()->getResult();
    }

    /** Obtenir le cumul des salaire de la période du prémier janvier à la date de depart
     * @throws Exception
     */
    public function getCumulSalaries(Personal $personal, mixed $endDate): float|int|null
    {
        // Créer une instance de DateTime pour le 1er janvier de l'année en cours
        $startDate = new DateTime(date('Y') . '-01-01');

        $query = $this->createQueryBuilder('pr')
            ->select('SUM(pr.brutAmount + pr.AncienneteAmount + pr.majorationAmount - pr.salaryTransport) as cumulSalaries')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->where('pr.personal = :personal')
            ->andWhere('campagnes.active = false')
            ->andWhere('campagnes.dateDebut BETWEEN :start AND :end')
            ->setParameter('personal', $personal)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery();

        $result = $query->getSingleResult();

        return $result['cumulSalaries'] ?? 0; // Retourne 0 si aucun résultat trouvé
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
            ->andWhere('pr.dateCreated BETWEEN :start AND :end')
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
            ->select('SUM(pr.imposableAmount + pr.AncienneteAmount + pr.majorationAmount) as amount_moyen')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->leftJoin('personal.departures', 'departures')
            ->where('pr.personal = :pr_personal')
            ->andWhere('pr.dateCreated BETWEEN :start AND :end')
            ->setParameter('pr_personal', $personal)
            ->setParameter('start', $lastTwelveMonths[0])
            ->setParameter('end', $lastTwelveMonths[10])
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
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
            ->andWhere('pr.dateCreated BETWEEN :start AND :end')
            ->setParameter('pr_personal', $personal)
            ->setParameter('start', $lastTwelveMonths[11])
            ->setParameter('end', $lastTwelveMonths[0])
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Determiner le salaire net du salarié
     * @throws NonUniqueResultException
     */
    public function getAmountNetPayer(Personal $personal): float|int|null
    {
        $query = $this->createQueryBuilder('pr')
            ->select('(pr.netPayer) as netPayer')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->where('pr.personal = :personal')
            ->andWhere('campagnes.ordinary = true')
            ->andWhere('campagnes.active = true')
            ->orderBy('pr.dateCreated', 'DESC')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->getQuery();
        $result = $query->getOneOrNullResult();
        return $result['netPayer'] ?? null;
    }

    /** Retourner les elements pour l'état des virement */
    public function getPayrollVirement(?string $typeVersement, bool $active, bool $type): ?array
    {
        return $this->createQueryBuilder('pr')
            ->select([
                'p.firstName as nom_salaried',
                'p.lastName as prenoms_salaried',
                'ac.bankId as banque',
                'ac.name as name_banque',
                'ac.codeAgence as code_agence',
                'ac.code as code_compte',
                'ac.numCompte as num_compte',
                'ac.rib as rib_compte',
                'pr.netPayer as net_payes',
                'p.modePaiement as mode_paiement',
                'c.dateDebut as debut',
                'c.dateFin as fin',
                'w.name as station'
            ])
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->leftJoin('p.workplace', 'w')
            ->leftJoin('p.accountBanks', 'ac')
            ->where('c.ordinary = :type')
            ->andWhere('c.active = :active')
            ->andWhere('c.status IN (:status)')
            ->andWhere('p.modePaiement = :type_versement')
            ->setParameter('type_versement', $typeVersement)
            ->setParameter('status', [Status::VALIDATED, Status::PENDING])
            ->setParameter('type', $type)
            ->setParameter('active', $active)
            ->getQuery()
            ->getResult();
    }

    public function getPayrollVirementByRoleEmployeur(?string $typeVersement, bool $active, bool $type): ?array
    {
        return $this->createQueryBuilder('pr')
            ->select([
                'p.firstName as nom_salaried',
                'p.lastName as prenoms_salaried',
                'ac.bankId as banque',
                'ac.name as name_banque',
                'ac.codeAgence as code_agence',
                'ac.code as code_compte',
                'ac.numCompte as num_compte',
                'ac.rib as rib_compte',
                'pr.netPayer as net_payes',
                'p.modePaiement as mode_paiement',
                'c.dateDebut as debut',
                'c.dateFin as fin',
                'p.service as station'
            ])
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->leftJoin('p.accountBanks', 'ac')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('c.ordinary = :type')
            ->andWhere('c.active = :active')
            ->andWhere('c.status IN (:status)')
            ->andWhere('p.modePaiement = :type_versement')
            ->andWhere("category_salarie.code IN (:code)")
            ->setParameter('type_versement', $typeVersement)
            ->setParameter('status', [Status::VALIDATED, Status::PENDING])
            ->setParameter('type', $type)
            ->setParameter('active', $active)
            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
            ->getQuery()
            ->getResult();
    }

    /** Retourner les elements pour l'etat des virement par periode */
    public function findPayrollVirementAnnuel(?string $typeVersement, bool $active, bool $type, array $months, int $year, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('pr');
        $qb
            ->select([
                'p.firstName as nom_salaried',
                'p.lastName as prenoms_salaried',
                'ac.bankId as banque',
                'ac.name as name_banque',
                'ac.codeAgence as code_agence',
                'ac.code as code_compte',
                'ac.numCompte as num_compte',
                'ac.rib as rib_compte',
                'pr.netPayer as net_payes',
                'p.modePaiement as mode_paiement',
                'c.dateDebut as debut',
                'c.dateFin as fin',
                'w.name as station',
                'MONTH(c.dateDebut) as start_month',
                'MONTH(c.dateFin) as end_month',
            ])
            ->join('pr.personal', 'p')
            ->leftJoin('p.workplace', 'w')
            ->join('pr.campagne', 'c')
            ->leftJoin('p.accountBanks', 'ac')
            ->where('c.ordinary = :type')
            ->andWhere('c.active = :active')
            ->andWhere('p.modePaiement = :type_versement')
            ->andWhere('c.status = :status')
            ->andWhere('pr.status = :payrol_statut')
            ->andWhere('MONTH(c.dateDebut) IN (?1) AND YEAR(c.dateDebut) = ?2');
        $qb->setParameters([1 => $months, 2 => $year, 'type' => $type, 'active' => $active, 'status' => Status::TERMINER, 'payrol_statut' => Status::PAYE, 'type_versement' => $typeVersement]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('p.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findPayrollVirementAnnuelByRoleEmployeur(?string $typeVersement, bool $active, bool $type, mixed $debut, mixed $fin, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('pr');
        $qb
            ->select([
                'p.firstName as nom_salaried',
                'p.lastName as prenoms_salaried',
                'ac.bankId as banque',
                'ac.name as name_banque',
                'ac.codeAgence as code_agence',
                'ac.code as code_compte',
                'ac.numCompte as num_compte',
                'ac.rib as rib_compte',
                'pr.netPayer as net_payes',
                'p.modePaiement as mode_paiement',
                'c.dateDebut as debut',
                'c.dateFin as fin',
                'p.service as station',
                'MONTH(c.dateDebut) as start_month',
                'MONTH(c.dateFin) as end_month',
            ])
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->leftJoin('p.accountBanks', 'ac')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('c.ordinary = :type')
            ->andWhere('c.active = :active')
            ->andWhere('p.modePaiement = :type_versement')
            ->andWhere('c.dateDebut >= :date_debut')
            ->andWhere('c.dateFin <= :date_fin')
            ->andWhere('c.status = :status')
            ->andWhere('pr.status = :payrol_statut')
            ->andWhere("category_salarie.code IN (:code)");
        $qb->setParameter('type', $type)
            ->setParameter('active', $active)
            ->setParameter('status', Status::TERMINER)
            ->setParameter('payrol_statut', Status::PAYE)
            ->setParameter('type_versement', $typeVersement)
            ->setParameter('date_debut', $debut)
            ->setParameter('date_fin', $fin)
            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS']);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('p.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findOperationByPayroll(array $type, string $status, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('payroll')
            ->select([
                'DATE(op.dateOperation) as date_operation',
                'op.typeOperations as type_operations',
                'personal.matricule as matricule_personal',
                'personal.firstName as name_personal',
                'personal.lastName as lastname_personal',
                'w.name as stations_personal',
                'payroll.remboursNet as remboursement_net',
                'payroll.remboursBrut as remboursement_brut',
                'payroll.retenueNet as retenue_net',
                'payroll.retenueBrut as retenue_brut',
                'op.status as status_operation',
                'op.id as operation_id',
                'payroll.netPayer as net_payer'
            ])
            ->join('payroll.personal', 'personal')
            ->leftJoin('personal.workplace', 'w')
            ->join('personal.operations', 'op')
            ->join('payroll.campagne', 'campagne')
            ->where('op.typeOperations IN (:types)')
            ->andWhere('op.status IN (:status)')
            ->andWhere('YEAR(op.dateOperation) = :year')
            ->andWhere('MONTH(op.dateOperation) = :month')
            ->andWhere('campagne.status IN (:campagne_status)')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $type)
            ->setParameter('status', $status)
            ->setParameter('campagne_status', [Status::VALIDATED, Status::PENDING])
            ->orderBy('op.typeOperations')
            ->getQuery()
            ->getResult();
    }

    public function findOperationByPayrollByRoleEmployer(array $type, string $status, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('payroll')
            ->select([
                'DATE(op.dateOperation) as date_operation',
                'op.typeOperations as type_operations',
                'personal.matricule as matricule_personal',
                'personal.firstName as name_personal',
                'personal.lastName as lastname_personal',
                'personal.service as stations_personal',
                'payroll.remboursNet as remboursement_net',
                'payroll.remboursBrut as remboursement_brut',
                'payroll.retenueNet as retenue_net',
                'payroll.retenueBrut as retenue_brut',
                'op.status as status_operation',
                'op.id as operation_id',
                'payroll.netPayer as net_payer'
            ])
            ->join('payroll.personal', 'personal')
            ->join('personal.operations', 'op')
            ->join('payroll.campagne', 'campagne')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('op.typeOperations IN (:types)')
            ->andWhere('op.status IN (:status)')
            ->andWhere('YEAR(op.dateOperation) = :year')
            ->andWhere('MONTH(op.dateOperation) = :month')
            ->andWhere('campagne.status =:campagne_status')
            ->andWhere("category_salarie.code IN (:code)")
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $type)
            ->setParameter('status', $status)
            ->setParameter('campagne_status', Status::VALIDATED)
            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
            ->orderBy('op.typeOperations')
            ->getQuery()
            ->getResult();
    }

    public function findOperationByPeriode(array $months, int $years, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'DATE(op.dateOperation) as date_operation',
                'op.typeOperations as type_operations',
                'personal.matricule as matricule_personal',
                'personal.firstName as name_personal',
                'personal.lastName as lastname_personal',
                'w.name as stations_personal',
                'payroll.remboursNet as remboursement_net',
                'payroll.remboursBrut as remboursement_brut',
                'payroll.retenueNet as retenue_net',
                'payroll.retenueBrut as retenue_brut',
                'op.status as status_operation',
                'op.id as operation_id',
                'payroll.netPayer as net_payer',
                'lastCampagne.dateDebut as last_campagne_date_debut',
            ])
            ->join('payroll.personal', 'personal')
            ->leftJoin('personal.workplace', 'w')
            ->join('personal.operations', 'op')
            ->join('payroll.campagne', 'campagne')
            ->join('op.campagne', 'lastCampagne')
            ->where('op.typeOperations IN (:types)')
            ->andWhere('op.status IN (:status)')
            ->andWhere('campagne.status =:campagne_status')
            ->andWhere('MONTH(op.dateOperation) IN (?1) AND YEAR(op.dateOperation) = ?2')
            ->orderBy('op.typeOperations');
        $qb->setParameters([1 => $months, 2 => $years, 'types' => [Status::REMBOURSEMENT, Status::RETENUES], 'status' => Status::VALIDATED, 'campagne_status' => Status::TERMINER]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findOperationByPeriodeByRoleEmployer(array $months, int $years, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'DATE(op.dateOperation) as date_operation',
                'op.typeOperations as type_operations',
                'personal.matricule as matricule_personal',
                'personal.firstName as name_personal',
                'personal.lastName as lastname_personal',
                'w.name as stations_personal',
                'payroll.remboursNet as remboursement_net',
                'payroll.remboursBrut as remboursement_brut',
                'payroll.retenueNet as retenue_net',
                'payroll.retenueBrut as retenue_brut',
                'op.status as status_operation',
                'op.id as operation_id',
                'payroll.netPayer as net_payer',
                'lastCampagne.dateDebut as last_campagne_date_debut',
            ])
            ->join('payroll.personal', 'personal')
            ->leftJoin('personal.workplace', 'w')
            ->join('payroll.campagne', 'campagne')
            ->join('op.campagne', 'lastCampagne')
            ->join('personal.operations', 'op')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('op.typeOperations IN (:types)')
            ->andWhere('op.status IN (:status)')
            ->andWhere('MONTH(op.dateOperation) IN (?1) AND YEAR(op.dateOperation) = ?2')
            ->andWhere("category_salarie.code IN (:code)")
            ->andWhere('campagne.status =:campagne_status')
            ->orderBy('op.typeOperations');
        $qb->setParameters(['1' => $months, '2' => $years, 'types' => [Status::REMBOURSEMENT, Status::RETENUES], 'status' => Status::VALIDATED, 'code' => ['OUVRIER / EMPLOYES', 'CHAUFFEURS'], 'campagne_status' => Status::TERMINER]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOnePayroll(?Campagne $campagne, ?Personal $personal): ?Payroll
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.campagne = :campagne')
            ->andWhere('pr.personal = :personal')
            ->andWhere('pr.status IN (:status)')
            ->setParameter('personal', $personal)
            ->setParameter('campagne', $campagne)
            ->setParameter('status', [Status::PAYE, Status::EN_ATTENTE])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDisaCurrentYear(mixed $years): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'personal.firstName as nom',
                'personal.lastName as prenoms',
                'personal.refCNPS',
                'YEAR(personal.birthday) as personal_birthday',
                'contract.dateEmbauche as date_embauche',
                'departure.date as date_depart',
                'payroll.matricule',
                'SUM(payroll.imposableAmount) as imposable_amount',
                'salary.smig',
                'payroll.createdAt',
                'campagnes.startedAt'
            ])
            ->distinct()
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->join('personal.contract', 'contract')
            ->leftJoin('personal.departures', 'departure')
            ->join('personal.salary', 'salary')
            ->where('campagnes.status = :status')
            ->andWhere('YEAR(payroll.dateCreated) = :year')
            ->andWhere('contract.typeContrat in (:type_contrat)')
            ->groupBy('personal.firstName', 'personal.lastName', 'personal.refCNPS', 'personal.birthday', 'contract.dateEmbauche', 'departure.date', 'payroll.matricule', 'salary.smig', 'payroll.createdAt', 'campagnes.startedAt')
            ->orderBy('personal.refCNPS');
        $qb
            ->setParameters([
                'year' => $years,
                'status' => Status::TERMINER,
                'type_contrat' => [Status::CDD, Status::CDDI, Status::CDI]
            ]);
        return $qb->getQuery()->getResult();
    }

    public function findEtatDeclaration(array $months, int $year, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->select([
                'personal.id as personal_id',
                'personal.firstName as nom',
                'personal.lastName as prenoms',
                'personal.refCNPS',
                'personal.older',
                'personal.uuid as personal_uuid',
                'YEAR(personal.birthday) as personal_birthday',
                'personal.numCmu as personal_cmu',
                'personal.numSs as num_ss',
                'personal.isCmu as is_cmu',
                'personal.conjointNumSs as conjoint_num_ss',
                'personal.conjoint as conjoint_name',
                'p.uuid as payroll_uuid',
                'p.matricule',
                'personal.genre',
                'p.dayOfPresence',
                'p.baseAmount',
                'p.sursalaire',
                'p.imposableAmount',
                'p.amountFPC',
                'p.amountTA',
                "p.numberPart",
                'p.brutAmount',
                "p.salaryTransport",
                "p.amountPrimePanier",
                "p.amountPrimeSalissure",
                "p.amountPrimeOutillage",
                "p.amountPrimeTenueTrav",
                "p.amountPrimeRendement",
                "p.salaryIts",
                "p.employeurIs",
                "p.aventageNonImposable",
                'p.dateEmbauche',
                'campagnes.startedAt',
                'campagnes.dateDebut as periode_debut',
                'campagnes.dateFin as periode_fin',
                'campagnes.ordinary'
            ])
            ->join('p.campagne', 'campagnes')
            ->join('p.personal', 'personal')
            ->where('campagnes.active = false')
            ->andWhere('campagnes.status = :status')
            ->andWhere('MONTH(campagnes.dateDebut) IN (?1) AND YEAR(campagnes.dateDebut) = ?2');
        $qb->setParameters(['1' => $months, '2' => $year, 'status' => Status::TERMINER]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findCampagneCmuNew(bool $isActive)
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select([
                'personal.id as personal_id',
                'personal.firstName as nom',
                'personal.lastName as prenoms',
                'personal.refCNPS',
                'personal.genre',
                'YEAR(personal.birthday) as personal_birthday',
                'payroll.matricule',
                'payroll.numCnps',
                'payroll.dateEmbauche',
                'payroll.createdAt',
                'campagnes.startedAt',
                'personal.numSs as num_ss',
                'personal.isCmu as is_cmu',
                'personal.numCmu as personal_cmu',
                'personal.conjointNumSs as conjoint_num_ss',
                'personal.conjoint as conjoint_name',
            ])
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->where('campagnes.active = :active');
        $qb
            ->setParameter('active', $isActive);
        return $qb->getQuery()->getResult();
    }
}
