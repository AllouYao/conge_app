<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\ChargePeople;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChargePeople>
 *
 * @method ChargePeople|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChargePeople|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChargePeople[]    findAll()
 * @method ChargePeople[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChargePeopleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChargePeople::class);
    }


}
