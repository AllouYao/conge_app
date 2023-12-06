<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Expr\Array_;

/**
 * @extends ServiceEntityRepository<Personal>
 *
 * @method Personal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Personal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Personal[]    findAll()
 * @method Personal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersonalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personal::class);
    }

//    /**
//     * @return Personal[] Returns an array of Personal objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Personal
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findAllPersonal(): array
    {
        $qb = $this->createQueryBuilder('p')->getQuery()->getResult();
        return array_map(function ($result) {
            return $result;
        }, $qb);
    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findPersonalSalaried(): array
    {
        return $this->createQueryBuilder('p')
            ->select([
                'category.intitule as categorie_intitule',
                'categorie_salary.name as categorie_name',
                'p.id',
                'p.uuid',
                'p.matricule as matricule',
                'p.firstName as personal_name',
                'p.lastName as personal_prenoms',
                'p.genre as personal_genre',
                'p.birthday as personal_birthday',
                'p.lieuNaissance as personal_lieu_naiss',
                'p.refCNPS as personal_numero_cnps',
                'p.piece as personal_piece',
                'p.refPiece as personal_numero_piece',
                'p.address as personal_adresse',
                'p.telephone as personal_telephone',
                'p.email as personal_email',
                'p.ancienity as personal_anciennete',
                'p.conjoint as personal_conjoint',
                'p.numCertificat as personal_marriage_certificat',
                'p.numExtraitActe as personal_marriage_acte',
                'p.etatCivil as personal_etat_civil',
                'p.niveauFormation as personal_niveau_formation',
                'p.modePaiement as personal_mode_paiement',
                'contract.typeContrat as type_contrat',
                'contract.dateEmbauche as contrat_date_embauche',
                'contract.dateEffet as contrat_date_effet',
                'contract.dateFin as contrat_date_fin',
                'contract.tempsContractuel as temps_contractuel',
                'salary.baseAmount as personal_salaire_base',
                'salary.sursalaire as personal_sursalaire',
                'salary.brutAmount as personal_salaire_brut',
                'salary.primeTransport as personal_prime_transport',
                'salary.primeLogement as personal_prime_logement',
                'salary.primeFonction as personal_prime_fonction',
                'salary.brutImposable as personal_salaire_imposable',
                'salary.totalPrimeJuridique as personal_total_prime_juridique',
                'avantage.numPiece personal_avantage_piece',
                'avantage.totalAvantage personal_avantage_total_amount',
                'charge_personals.amountIts as charge_personal_its',
                'charge_personals.amountCNPS as charge_personal_cnps',
                'charge_personals.amountCMU as charge_personal_cmu',
                'charge_personals.AmountTotalChargePersonal as total_charge_personal',
                'charge_personals.numPart as charge_personal_nombre_part',
                'charge_employeurs.amountIS as charge_employeur_is',
                'charge_employeurs.amountFDFP as charge_employeur_fdfp',
                'charge_employeurs.amountCR as charge_employeur_cr',
                'charge_employeurs.amountPF as charge_employeur_pf',
                'charge_employeurs.amountAT as charge_employeur_at',
                'charge_employeurs.amountCMU as charge_employeur_cmu',
                'charge_employeurs.totalRetenuCNPS as total_retenu_cnps',
                'charge_employeurs.totalChargeEmployeur as total_charge_employeur',
                'account_banks.bankId as name_banque',
                'account_banks.code as code_banque',
                'account_banks.numCompte as numero_compte',
                'account_banks.rib as rib',
            ])
            ->leftJoin('p.categorie', 'category')
            ->leftJoin('p.contract', 'contract')
            ->leftJoin('p.salary', 'salary')
            ->leftJoin('p.accountBanks', 'account_banks')
            ->join('category.categorySalarie', 'categorie_salary')
            ->leftJoin('salary.avantage', 'avantage')
            ->join('p.chargePersonals', 'charge_personals')
            ->join('p.chargeEmployeurs', 'charge_employeurs')
            ->getQuery()
            ->getResult();
    }
}
