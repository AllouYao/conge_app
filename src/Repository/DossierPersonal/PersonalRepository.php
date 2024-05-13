<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Entity\User;
use App\Utils\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

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
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, Personal::class);
    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findDisablePersonal(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.active = false')
            ->orderBy('p.matricule', 'ASC')
            ->getQuery()
            ->getResult();
        return array_map(function ($result) {
            return $result;
        }, $qb);
    }

    public function findAllPersonalOnCampain(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.contract', 'contract')
            ->leftJoin('p.departures', 'departures')
            ->where('departures.id IS NULL')
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('p.active = true')
            ->setParameter('type', [Status::CDI, Status::CDD, Status::CDDI])
            ->orderBy('p.matricule', 'ASC')
            ->getQuery()
            ->getResult();
        return array_map(function ($result) {
            return $result;
        }, $qb);
    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findPersoRequest(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $qb = $this->createQueryBuilder('p');
        $em = $qb->getEntityManager();
        return $qb
            ->select([
                'p.id',
                'p.matricule',
                'p.firstName',
                'p.lastName',
            ])
            ->join('p.contract', 'contract')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->leftJoin('p.departures', 'departures')
            ->where(
                $qb->expr()
                    ->in(
                        'category_salarie.id',
                        $em
                            ->createQueryBuilder()
                            ->select('c.id')
                            ->from('App:User', 'u')
                            ->join('u.categories', 'c')
                            ->where($qb->expr()->eq('u.id', $user->getId()))
                            ->getDQL()
                    )
            )
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('p.active = true')
            ->andWhere('departures.id IS NULL')
            ->setParameter('type', [Status::CDI, Status::CDDI, Status::CDD])
            ->orderBy('p.matricule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findPersonalSalaried(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $qb = $this->createQueryBuilder('p');
        $em = $qb->getEntityManager();
        return $qb
            ->select([
                'category.intitule as categorie_intitule',
                'categorie_salary.name as categorie_name',
                'p.id as personal_id',
                'p.uuid',
                'p.older',
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
                'p.modePaiement as mode_paiement',
                'job.name as personal_fonction',
                'work.name as personal_service',
                'p.active as active',
                'contract.typeContrat as type_contrat',
                'contract.dateEmbauche as contrat_date_embauche',
                'contract.dateEffet as contrat_date_effet',
                'contract.dateFin as contrat_date_fin',
                'contract.tempsContractuel as temps_contractuel',
                'salary.baseAmount as personal_salaire_base',
                'salary.sursalaire as personal_sursalaire',
                'salary.brutAmount as personal_salaire_brut',
                'salary.primeTransport as personal_prime_transport',
                'salary.brutImposable as personal_salaire_imposable',
                'salary.totalPrimeJuridique as personal_total_prime_juridique',
                'avantage.numPiece personal_avantage_piece',
                'avantage.totalAvantage personal_avantage_total_amount',
                'account_banks.bankId as name_banque',
                'account_banks.code as code_banque',
                'account_banks.numCompte as numero_compte',
                'account_banks.rib as rib',
            ])
            ->leftJoin('p.job', 'job')
            ->leftJoin('p.workplace', 'work')
            ->leftJoin('p.categorie', 'category')
            ->join('p.contract', 'contract')
            ->join('p.salary', 'salary')
            ->leftJoin('p.accountBanks', 'account_banks')
            ->join('category.categorySalarie', 'categorie_salary')
            ->leftJoin('salary.avantage', 'avantage')
            ->leftJoin('p.departures', 'departures')
            ->where(
                $qb->expr()
                    ->in(
                        'categorie_salary.id',
                        $em
                            ->createQueryBuilder()
                            ->select('c.id')
                            ->from('App:User', 'u')
                            ->join('u.categories', 'c')
                            ->where($qb->expr()->eq('u.id', $user->getId()))
                            ->getDQL()
                    )
            )
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('departures.id IS NULL')
            ->setParameter('type', [Status::CDI, Status::CDDI, Status::CDD])
            ->orderBy('p.matricule', 'ASC')
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
            ->andWhere('campagnes.status = :status')
            ->setParameter('status', Status::VALIDATED)
            ->getQuery()
            ->getResult();
    }


    public function findLastId(): float|bool|int|string|null
    {
        return $this->createQueryBuilder('t')
            ->select('MAX(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function areAllUsersActivated(): bool
    {
        $countActivatedUsers = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.active = :isActive')
            ->setParameter('isActive', true)
            ->getQuery()
            ->getSingleScalarResult();

        $countAllUsers = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $countActivatedUsers == $countAllUsers;
    }


    /** Gestion du formulaire d'ajout du personal au niveau des charge people  */
    public function findPersonalWithChargePeople(): \Doctrine\ORM\QueryBuilder
    {
        /** @var User $user */
        $users = $this->security->getUser();
        $query = $this->createQueryBuilder('p');
        $manager = $query->getEntityManager();
        $query
            ->join('p.contract', 'contract')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->leftJoin('p.departures', 'departures')
            ->leftJoin('p.chargePeople', 'charge_people')
            ->where(
                $query->expr()
                    ->in(
                        'category_salarie.id',
                        $manager
                            ->createQueryBuilder()
                            ->select('c.id')
                            ->from('App:User', 'u')
                            ->join('u.categories', 'c')
                            ->where($query->expr()->eq('u.id', $users->getId()))
                            ->getDQL()
                    )
            )
            ->andWhere('p.active = true')
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('departures.id IS NULL')
            ->andWhere('charge_people.id IS NULL')
            ->setParameter('type', [Status::CDI, Status::CDDI, Status::CDD])
            ->orderBy('p.firstName', 'ASC');
        return $query;
    }

    public function findEditPersonalWithChargePeople(Personal $personal): \Doctrine\ORM\QueryBuilder
    {
        /** @var User $user */
        $users = $this->security->getUser();
        $query = $this->createQueryBuilder('p');
        $manager = $query->getEntityManager();
        $query
            ->join('p.contract', 'contract')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->leftJoin('p.departures', 'departures')
            ->leftJoin('p.chargePeople', 'charge_people')
            ->where(
                $query->expr()
                    ->in(
                        'category_salarie.id',
                        $manager
                            ->createQueryBuilder()
                            ->select('c.id')
                            ->from('App:User', 'u')
                            ->join('u.categories', 'c')
                            ->where($query->expr()->eq('u.id', $users->getId()))
                            ->getDQL()
                    )
            )
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('p.active = true')
            ->andWhere('charge_people.id IS NOT NULL')
            ->andWhere('p.id = :personal_id')
            ->setParameter('type', [Status::CDI, Status::CDDI, Status::CDD])
            ->setParameter('personal_id', $personal->getId())
            ->orderBy('p.firstName', 'ASC');
        return $query;
    }

    /**
     * @return Personal[] Returns an array of Personal objects
     */
    public function findPersonalWithChargePeaple(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $qb = $this->createQueryBuilder('p');
        $em = $qb->getEntityManager();
        return $qb
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'categorySalarie')
            ->join('p.chargePeople', 'charge_people')
            ->join('p.contract', 'contract')
            ->where('charge_people.id is not null')
            ->andWhere(
                $qb->expr()
                    ->in(
                        'categorySalarie.id',
                        $em
                            ->createQueryBuilder()
                            ->select('c.id')
                            ->from('App:User', 'u')
                            ->join('u.categories', 'c')
                            ->where($qb->expr()->eq('u.id', $user->getId()))
                            ->getDQL()
                    )
            )
            ->andWhere('p.active = true')
            ->andWhere('contract.typeContrat IN (:type)')
            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
            ->getQuery()
            ->getResult();
    }

    /** Gestion du formulaire d'ajout du personal au niveau des charge people  */
    public function findPersonalWithAcompteBank(): \Doctrine\ORM\QueryBuilder
    {
        /** @var User $user */
        $users = $this->security->getUser();
        $query = $this->createQueryBuilder('p');
        $manager = $query->getEntityManager();
        $query
            ->join('p.contract', 'contract')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->leftJoin('p.departures', 'departures')
            ->leftJoin('p.accountBanks', 'account_banks')
            ->where(
                $query->expr()
                    ->in(
                        'category_salarie.id',
                        $manager
                            ->createQueryBuilder()
                            ->select('c.id')
                            ->from('App:User', 'u')
                            ->join('u.categories', 'c')
                            ->where($query->expr()->eq('u.id', $users->getId()))
                            ->getDQL()
                    )
            )
            ->andWhere('p.active = true')
            ->andWhere('p.modePaiement IN (:mode_paiement)')
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('departures.id IS NULL')
            ->andWhere('account_banks.id IS NULL')
            ->setParameter('type', [Status::CDI, Status::CDDI, Status::CDD])
            ->setParameter('mode_paiement', [Status::VIREMENT, Status::CHEQUE])
            ->orderBy('p.firstName', 'ASC');
        return $query;
    }

    public function findEditPersonalWithAcompteBank(Personal $personal): \Doctrine\ORM\QueryBuilder
    {
        /** @var User $user */
        $users = $this->security->getUser();
        $query = $this->createQueryBuilder('p');
        $manager = $query->getEntityManager();
        $query
            ->join('p.contract', 'contract')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->leftJoin('p.departures', 'departures')
            ->leftJoin('p.accountBanks', 'account_banks')
            ->where(
                $query->expr()
                    ->in(
                        'category_salarie.id',
                        $manager
                            ->createQueryBuilder()
                            ->select('c.id')
                            ->from('App:User', 'u')
                            ->join('u.categories', 'c')
                            ->where($query->expr()->eq('u.id', $users->getId()))
                            ->getDQL()
                    )
            )
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('p.active = true')
            ->andWhere('p.modePaiement IN (:mode_paiement)')
            ->andWhere('p.id = :personal_id')
            ->andWhere('account_banks.id IS NOT NULL')
            ->setParameter('type', [Status::CDI, Status::CDDI, Status::CDD])
            ->setParameter('personal_id', $personal->getId())
            ->setParameter('mode_paiement', [Status::VIREMENT, Status::CHEQUE])
            ->orderBy('p.firstName', 'ASC');
        return $query;
    }

    /** Gestion du formulaire d'ajout du personal dans les formulaire non specific */
    public function findPersoBuilder(): \Doctrine\ORM\QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $qb = $this->createQueryBuilder('p');
        $em = $qb->getEntityManager();

        $qb
            ->join('p.contract', 'contract')
            ->join('p.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->leftJoin('p.departures', 'departures')
            ->where(
                $qb->expr()
                    ->in(
                        'category_salarie.id',
                        $em
                            ->createQueryBuilder()
                            ->select('c.id')
                            ->from('App:User', 'u')
                            ->join('u.categories', 'c')
                            ->where($qb->expr()->eq('u.id', $user->getId()))
                            ->getDQL()
                    )
            )
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('p.active = true')
            ->andWhere('departures.id IS NULL')
            ->setParameter('type', [Status::CDI, Status::CDDI, Status::CDD])
            ->orderBy('p.firstName', 'ASC');
        return $qb;
    }

}
