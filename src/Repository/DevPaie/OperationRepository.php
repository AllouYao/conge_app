<?php

namespace App\Repository\DevPaie;

use App\Entity\DevPaie\Operation;
use App\Entity\DossierPersonal\Personal;
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

    public function findOperationByType(?array $types): ?array
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->where('o.typeOperations IN (:types)')
            ->setParameter('types', $types)
            ->orderBy('o.typeOperations')
            ->getQuery()
            ->getResult();
    }

    public function findOperationByTypeAndStatus(string $type, ?array $status): ?array
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
            ->where('o.typeOperations =:types')
            ->andWhere('o.status IN (:status)')
            ->setParameter('types', $type)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function findOperationByPersonal(string $type, string $status, Personal $personal): ?Operation
    {
        return $this->createQueryBuilder('o')
            ->where('o.personal = :personal')
            ->andWhere('o.typeOperations = :type_operations')
            ->andWhere('o.status = :status')
            ->setParameter('personal', $personal)
            ->setParameter('type_operations', $type)
            ->setParameter('status', $status)
            ->getQuery()
            ->getOneOrNullResult();
    }
}