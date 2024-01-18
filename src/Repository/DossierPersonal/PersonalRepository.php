<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Utils\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
    public function findAllPersonalDepart(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.departures', 'departures')
            ->join('p.contract', 'contract')
            ->where('departures.id is not null')
            ->andWhere('contract.id is not null')->getQuery()->getResult();
        return array_map(function ($result) {
            return $result;
        }, $qb);
    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findAllWomanPersonal(): array
    {
        $qb = $this->createQueryBuilder('p')->where('p.genre = :genre')->setParameter('genre', Status::FEMININ)->getQuery()->getResult();
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
                'p.id as personal_id',
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
                'p.conjoint as personal_conjoint',
                'p.numCertificat as personal_marriage_certificat',
                'p.numExtraitActe as personal_marriage_acte',
                'p.etatCivil as personal_etat_civil',
                'p.niveauFormation as personal_niveau_formation',
                'p.modePaiement as personal_mode_paiement',
                'p.fonction as personal_fonction',
                'p.service as personal_service',
                'contract.typeContrat as type_contrat',
                'contract.dateEmbauche as contrat_date_embauche',
                'contract.dateEffet as contrat_date_effet',
                'contract.dateFin as contrat_date_fin',
                'contract.tempsContractuel as temps_contractuel',
                'salary.baseAmount as personal_salaire_base',
                'salary.sursalaire as personal_sursalaire',
                'salary.brutAmount as personal_salaire_brut',
                'salary.primeTransport as personal_prime_transport',
                //'salary.primeLogement as personal_prime_logement',
                //'salary.primeFonction as personal_prime_fonction',
                'salary.brutImposable as personal_salaire_imposable',
                'salary.totalPrimeJuridique as personal_total_prime_juridique',
                'avantage.numPiece personal_avantage_piece',
                'avantage.totalAvantage personal_avantage_total_amount',
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
            ->getQuery()
            ->getResult();
    }


    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findPersonalWithContract(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.campagnes', 'campagnes')
            ->leftJoin('p.contract', 'contract')
            ->where('contract.id IS NOT NULL')
            ->andWhere('campagnes.id  IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findPersonalWithChargePeaple(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.chargePeople', 'charge_people')
            ->where('charge_people.id is not null')
            ->getQuery()
            ->getResult();
    }
}
