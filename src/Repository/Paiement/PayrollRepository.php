<?php

namespace App\Repository\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Entity\Paiement\Payroll;
use App\Utils\Status;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * @param bool $active
     * @param Personal $personal
     * @return Payroll[]|null
     * Retourne le dictionnaire des salaire de la campagne en fonction du type et du status de la campagne pour les bulletins
     */
    public function findBulletinByCampaign(bool $active, Personal $personal): ?array
    {
        return $this->createQueryBuilder('pr')
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->andWhere('c.active = :active')
            ->andWhere('p.id = :personal')
            ->setParameter('active', $active)
            ->setParameter('personal', $personal->getId())
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
            ->andWhere('categorySalarie.code = :code_employe OR   categorySalarie.code = :code_chauffeur')
            ->andWhere('c.active = :active')
            ->setParameter('active', $active)
            ->setParameter('code_employe', 'OE')
            ->setParameter('code_chauffeur', 'CH')
            ->getQuery()->getResult();
    }

    /** Etat des salaire periodique en fonction des rôles */
    public function findEtatSalaire(mixed $mouth1, mixed $mouth2, ?int $personalId): array
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
            ->where('campagnes.active = false')
            ->andWhere('personal.active = true')
            ->andWhere('campagnes.status = :status')
            ->andWhere('payroll.status = :payroll_status')
            ->andWhere('campagnes.dateDebut BETWEEN ?1 AND ?2');
        $qb->setParameters(['1' => $mouth1, '2' => $mouth2, 'status' => Status::TERMINER, 'payroll_status' => Status::PAYE]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findEtatSalaireByRoleEmployer(mixed $mouth1, mixed $mouth2, ?int $personalId): array
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
            ->andWhere('campagnes.dateDebut BETWEEN ?1 AND ?2');
        $qb->setParameters(['1' => $mouth1, '2' => $mouth2, 'status' => Status::TERMINER, 'payroll_status' => Status::PAYE, 'code' => ['OUVRIER / EMPLOYES', 'CHAUFFEURS']]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findByPeriode(mixed $mouth1, mixed $mouth2, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
            ->select()
            ->join('payroll.campagne', 'campagnes')
            ->join('payroll.personal', 'personal')
            ->where('campagnes.active = false')
            ->andWhere('personal.active = true')
            ->andWhere('campagnes.dateDebut BETWEEN ?1 AND ?2');
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
            ->andWhere('YEAR(payroll.dateCreated) = :year')
            ->andWhere('MONTH(payroll.dateCreated) = :month');
        $qb
            ->setParameter('active', $campagne)
            ->setParameter('year', $years)
            ->setParameter('month', $month);
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

    /** Determiner le dernier salaire payer au salarie */
    public function getLastAmountMensuel(Personal $personal): float|int|null
    {
        $query = $this->createQueryBuilder('pr')
            ->select('(pr.brutAmount + pr.AncienneteAmount + pr.majorationAmount - pr.salaryTransport) as cumulSalaries')
            ->join('pr.personal', 'personal')
            ->join('pr.campagne', 'campagnes')
            ->where('pr.personal = :personal')
            ->andWhere('campagnes.ordinary = true')
            ->orderBy('pr.dateCreated', 'DESC')
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
            ->andWhere('pr.dateCreated BETWEEN :start AND :end')
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
            ->orderBy('pr.dateCreated', 'DESC')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->getQuery();
        $result = $query->getOneOrNullResult();
        return $result['netPayer'] ?? null;
    }

    /** Retourne le dictionnaire  de salaire en fonction de l'id de la campagne */
    public function findPayrollByCampainId(?int $campainId): ?array
    {
        return $this->createQueryBuilder('pr')
            ->select([
                'p.id as personal_id',
                'p.matricule as matricule',
                'p.firstName as first_name',
                'p.lastName as last_name',
                'pr.brutAmount as brut_amount',
                'pr.netPayer as net_payer',
            ])
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->where('c.ordinary = true')
            ->andWhere('c.active = false')
            ->andWhere('c.id = :campain_id')
            ->setParameter('campain_id', $campainId)
            ->orderBy('pr.id', 'ASC')
            ->getQuery()->getResult();
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
                'p.service as station'
            ])
            ->join('pr.personal', 'p')
            ->join('pr.campagne', 'c')
            ->leftJoin('p.accountBanks', 'ac')
            ->where('c.ordinary = :type')
            ->andWhere('c.active = :active')
            ->andWhere('c.status = :status')
            ->andWhere('p.modePaiement = :type_versement')
            ->setParameter('type_versement', $typeVersement)
            ->setParameter('status', Status::VALIDATED)
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
            ->andWhere('c.status = :status')
            ->andWhere('p.modePaiement = :type_versement')
            ->andWhere("category_salarie.code IN (:code)")
            ->setParameter('type_versement', $typeVersement)
            ->setParameter('status', Status::VALIDATED)
            ->setParameter('type', $type)
            ->setParameter('active', $active)
            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
            ->getQuery()
            ->getResult();
    }

    /** Retourner les elements pour l'etat des virement par periode */
    public function findPayrollVirementAnnuel(?string $typeVersement, bool $active, bool $type, mixed $debut, mixed $fin, ?int $personalId): array
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
            ->where('c.ordinary = :type')
            ->andWhere('c.active = :active')
            ->andWhere('p.modePaiement = :type_versement')
            ->andWhere('c.dateDebut >= :date_debut')
            ->andWhere('c.dateFin <= :date_fin')
            ->andWhere('c.status = :status')
            ->andWhere('pr.status = :payrol_statut');
        $qb->setParameter('type', $type)
            ->setParameter('active', $active)
            ->setParameter('status', Status::VALIDATED)
            ->setParameter('payrol_statut', Status::PAYE)
            ->setParameter('type_versement', $typeVersement)
            ->setParameter('date_debut', $debut)
            ->setParameter('date_fin', $fin);
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
            ->where('op.typeOperations IN (:types)')
            ->andWhere('op.status IN (:status)')
            ->andWhere('YEAR(op.dateOperation) = :year')
            ->andWhere('MONTH(op.dateOperation) = :month')
            ->andWhere('campagne.status =:campagne_status')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $type)
            ->setParameter('status', $status)
            ->setParameter('campagne_status', Status::VALIDATED)
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

    public function findOperationByPeriode(mixed $start, mixed $end, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
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
                'payroll.netPayer as net_payer',
                'lastCampagne.dateDebut as last_campagne_date_debut',
            ])
            ->join('payroll.personal', 'personal')
            ->join('personal.operations', 'op')
            ->join('payroll.campagne', 'campagne')
            ->join('op.campagne', 'lastCampagne')
            ->where('op.typeOperations IN (:types)')
            ->andWhere('op.status IN (:status)')
            ->andWhere('campagne.status =:campagne_status')
            ->andWhere('op.dateOperation BETWEEN ?1 AND ?2 ')
            ->orderBy('op.typeOperations');
        $qb->setParameters(['1' => $start, '2' => $end, 'types' => [Status::REMBOURSEMENT, Status::RETENUES], 'status' => Status::VALIDATED, 'campagne_status' => Status::TERMINER]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

    public function findOperationByPeriodeByRoleEmployer(mixed $start, mixed $end, ?int $personalId): array
    {
        $qb = $this->createQueryBuilder('payroll');
        $qb
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
                'payroll.netPayer as net_payer',
                'lastCampagne.dateDebut as last_campagne_date_debut',
            ])
            ->join('payroll.personal', 'personal')
            ->join('payroll.campagne', 'campagne')
            ->join('op.campagne', 'lastCampagne')
            ->join('personal.operations', 'op')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('op.typeOperations IN (:types)')
            ->andWhere('op.status IN (:status)')
            ->andWhere('op.dateOperation BETWEEN ?1 AND ?2 ')
            ->andWhere("category_salarie.code IN (:code)")
            ->andWhere('campagne.status =:campagne_status')
            ->orderBy('op.typeOperations');
        $qb->setParameters(['1' => $start, '2' => $end, 'types' => [Status::REMBOURSEMENT, Status::RETENUES], 'status' => Status::VALIDATED, 'code' => ['OUVRIER / EMPLOYES', 'CHAUFFEURS'], 'campagne_status' => Status::TERMINER]);
        if ($personalId) {
            $qb->andWhere($qb->expr()->eq('personal.id', $personalId));
        }
        return $qb->getQuery()->getResult();
    }

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
}
