<?php

namespace App\Repository\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campagne>
 *
 * @method Campagne|null find($id, $lockMode = null, $lockVersion = null)
 * @method Campagne|null findOneBy(array $criteria, array $orderBy = null)
 * @method Campagne[]    findAll()
 * @method Campagne[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampagneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campagne::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function active(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->where("c.active = true")
            ->setMaxResults(1)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function lastCampagne(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->join('c.personal', 'p')
            ->leftJoin('p.chargePersonals', 'ch')
            ->where("c.active = false")
            ->orderBy("c.id", "DESC")
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNbrePersonal(Campagne $campagne): int
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->join("c.personal", "p")
            ->where("c = :c")
            ->andWhere('p IS NOT NULL')
            ->setParameter('c', $campagne);
        return count($qb->getQuery()->getResult());
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findLastCampaign()
    {
        return $this->createQueryBuilder('c')
            ->orderBy("c.id", "DESC")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function checkPersonalInCampagne(Personal $personal): array
    {
        return $this->createQueryBuilder('c')
            ->join("c.personal", 'p')
            ->where("p = :personal")
            ->setParameter('personal', $personal)
            ->getQuery()->getScalarResult();
    }


}
