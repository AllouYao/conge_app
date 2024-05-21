<?php

namespace App\Repository;

use App\Entity\Personal;
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



}
