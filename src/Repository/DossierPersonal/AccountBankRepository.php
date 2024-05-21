<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\AccountBank;
use App\Utils\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountBank>
 *
 * @method AccountBank|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountBank|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountBank[]    findAll()
 * @method AccountBank[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountBankRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountBank::class);
    }

    public function findAccountBank(): ?array
    {
        return $this->createQueryBuilder('acc')
            ->join('acc.personal', 'p')
            ->join('p.contract', 'contract')
            ->where('p.modePaiement IN (:mode_paiement)')
            ->andWhere('p.active = true')
            ->andWhere('contract.typeContrat IN (:type)')
            ->setParameter('mode_paiement', [Status::VIREMENT, Status::CHEQUE])
            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
            ->orderBy('p.matricule', 'ASC')
            ->getQuery()->getResult();
    }

}
