<?php

namespace App\Repository\Settings;

use App\Entity\Settings\Smig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Smig>
 *
 * @method Smig|null find($id, $lockMode = null, $lockVersion = null)
 * @method Smig|null findOneBy(array $criteria, array $orderBy = null)
 * @method Smig[]    findAll()
 * @method Smig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Smig::class);
    }

    public function active(): ?Smig
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = true')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

public function findSmigActive(): ?array
    {
        return $this->createQueryBuilder('s')
            ->join('s.categorySalaries', 'category_salaries')
            ->where('s.isActive = true')
            ->getQuery()
            ->getResult();
    }
}
