<?php

namespace App\Repository\Impots;

use App\Entity\Impots\CategoryCharge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryCharge>
 *
 * @method CategoryCharge|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoryCharge|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoryCharge[]    findAll()
 * @method CategoryCharge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryChargeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryCharge::class);
    }

}
