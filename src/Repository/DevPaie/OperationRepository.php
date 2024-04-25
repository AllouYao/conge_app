<?php

namespace App\Repository\DevPaie;

use App\Entity\DevPaie\Operation;
use App\Entity\DossierPersonal\Personal;
use App\Utils\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 *
 * @method Operation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operation[]    findAll()
 * @method Operation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    public function findOperationByType(?array $types, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->join('personal.contract', 'contract')
            ->leftJoin('personal.departures', 'departures')
            ->where('o.typeOperations IN (:types)')
            ->andWhere('YEAR(o.dateOperation) = :year')
            ->andWhere('MONTH(o.dateOperation) = :month')
            ->andWhere('personal.active = true')
            ->andWhere('departures.id IS NULL')
            ->andWhere('contract.typeContrat IN (:type)')
            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $types)
            ->orderBy('o.typeOperations')
            ->orderBy('personal.matricule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOperationByTypeAndEmployerRole(?array $types, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->join('personal.contract', 'contract')
            ->leftJoin('personal.departures', 'departures')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('o.typeOperations IN (:types)')
            ->andWhere('YEAR(o.dateOperation) = :year')
            ->andWhere('MONTH(o.dateOperation) = :month')
            ->andWhere('personal.active = true')
            ->andWhere('departures.id IS NULL')
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere("category_salarie.code IN (:code)")
            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $types)
            ->orderBy('o.typeOperations')
            ->orderBy('personal.matricule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOperationByTypeAndStatus(string $type, ?array $status, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('o')
            ->select([
                'DATE(o.dateOperation) as date_operation',
                'o.typeOperations as type_operations',
                'personal.matricule as matricule_personal',
                'personal.firstName as name_personal',
                'personal.lastName as lastname_personal',
                'job.name as stations_personal',
                'o.amountBrut as montant_brut',
                'o.amountNet as montant_net',
                'o.status as status_operation',
                'o.id as operation_id'
            ])
            ->join('o.personal', 'personal')
            ->join('personal.job', 'job')
            ->where('o.typeOperations =:types')
            ->andWhere('o.status IN (:status)')
            ->andWhere('YEAR(o.dateOperation) = :year')
            ->andWhere('MONTH(o.dateOperation) = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $type)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function findOperationByTypeAndStatusByEmployerRole(string $type, ?array $status, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('o')
            ->select([
                'DATE(o.dateOperation) as date_operation',
                'o.typeOperations as type_operations',
                'personal.matricule as matricule_personal',
                'personal.firstName as name_personal',
                'personal.lastName as lastname_personal',
                'personal.service as stations_personal',
                'o.amountBrut as montant_brut',
                'o.amountNet as montant_net',
                'o.status as status_operation',
                'o.id as operation_id'
            ])
            ->join('o.personal', 'personal')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->join('personal.contract', 'contract')
            ->leftJoin('personal.departures', 'departures')
            ->where('o.typeOperations =:types')
            ->andWhere('personal.active = true')
            ->andWhere('o.status IN (:status)')
            ->andWhere('YEAR(o.dateOperation) = :year')
            ->andWhere('MONTH(o.dateOperation) = :month')
            ->andWhere('departures.id IS NULL')
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere("category_salarie.code IN (:code)")
            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $type)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function findOperationByPersonal(string $type, string $status, Personal $personal, int $month, int $year): ?Operation
    {
        return $this->createQueryBuilder('o')
            ->where('o.personal = :personal')
            ->andWhere('o.typeOperations = :type_operations')
            ->andWhere('o.status = :status')
            ->andWhere('YEAR(o.dateOperation) = :year')
            ->andWhere('MONTH(o.dateOperation) = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('personal', $personal)
            ->setParameter('type_operations', $type)
            ->setParameter('status', $status)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOperationPretByPersonal(string $type, Personal $personal): ?Operation
    {
        return $this->createQueryBuilder('o')
            ->where('o.personal = :personal')
            ->andWhere('o.typeOperations = :type_operations')
            ->andWhere("o.statusPay is null OR o.statusPay != 'REFUND'")
            ->andWhere("o.status = 'VALIDEE'")
            ->setParameter('personal', $personal)
            ->setParameter('type_operations', $type)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAcomptAndPretByStatus(?array $types, string $status, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->join('personal.contract', 'contract')
            ->leftJoin('personal.departures', 'departures')
            ->where('o.typeOperations IN (:types)')
            ->andWhere('YEAR(o.dateOperation) = :year')
            ->andWhere('MONTH(o.dateOperation) = :month')
            ->andWhere('personal.active = true')
            ->andWhere('departures.id IS NULL')
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere('o.status = :status')
            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $types)
            ->setParameter('status', $status)
            ->orderBy('o.typeOperations')
            ->orderBy('personal.matricule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAcomptAndPretByStatusAndEmployerRole(?array $types, string $status, int $month, int $year): ?array
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->join('personal.contract', 'contract')
            ->leftJoin('personal.departures', 'departures')
            ->join('personal.categorie', 'category')
            ->join('category.categorySalarie', 'category_salarie')
            ->where('o.typeOperations IN (:types)')
            ->andWhere('YEAR(o.dateOperation) = :year')
            ->andWhere('MONTH(o.dateOperation) = :month')
            ->andWhere('personal.active = true')
            ->andWhere('departures.id IS NULL')
            ->andWhere('contract.typeContrat IN (:type)')
            ->andWhere("category_salarie.code IN (:code)")
            ->andWhere('o.status = :status')
            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('types', $types)
            ->setParameter('status', $status)
            ->orderBy('o.typeOperations')
            ->orderBy('personal.matricule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOperationRegullByPeriode(mixed $start, mixed $end, int $personal_id): ?Operation
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->where('personal.id = :personal_id')
            ->andWhere('o.dateOperation >= :start_date')
            ->andWhere('o.dateOperation <= :end_date')
            ->andWhere('o.status = :status')
            ->andWhere('o.typeOperations in (:type_operations)')
            ->setParameter('start_date', $start)
            ->setParameter('end_date', $end)
            ->setParameter('status', Status::VALIDATED)
            ->setParameter('type_operations', [Status::REMBOURSEMENT, Status::RETENUES])
            ->setParameter('personal_id', $personal_id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOperationAcompteAndPreByPeriod(mixed $start, mixed $end, int $personal_id): ?Operation
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->where('personal.id = :personal_id')
            ->andWhere('o.dateOperation >= :start_date')
            ->andWhere('o.dateOperation <= :end_date')
            ->andWhere('o.status = :status')
            ->andWhere('o.typeOperations in (:type_operations)')
            ->setParameter('start_date', $start)
            ->setParameter('end_date', $end)
            ->setParameter('status', Status::VALIDATED)
            ->setParameter('type_operations', [Status::ACOMPTE, Status::PRET])
            ->setParameter('personal_id', $personal_id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
