<?php

namespace App\Repository;

use App\Entity\TypeConge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeConge>
 *
 * @method TypeConge|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeConge|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeConge[]    findAll()
 * @method TypeConge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeConge::class);
    }
}

