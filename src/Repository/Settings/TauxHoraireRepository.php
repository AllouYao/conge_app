<?php

namespace App\Repository\Settings;

use App\Entity\Settings\TauxHoraire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TauxHoraire>
 *
 * @method TauxHoraire|null find($id, $lockMode = null, $lockVersion = null)
 * @method TauxHoraire|null findOneBy(array $criteria, array $orderBy = null)
 * @method TauxHoraire[]    findAll()
 * @method TauxHoraire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TauxHoraireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TauxHoraire::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function active(): ?TauxHoraire
    {
        return $this->createQueryBuilder('th')
            ->where('th.isActive = true')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
