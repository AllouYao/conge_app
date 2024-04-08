<?php

namespace App\Repository\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Utils\Status;
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

    /**
     * @throws NonUniqueResultException
     */
    public function getExceptionalCampagne(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->where("c.active = true")
            ->andWhere("c.ordinary = false")
            ->setMaxResults(1)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getOrdinaryCampagne(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->where("c.active = true")
            ->andWhere("c.ordinary = true")
            ->setMaxResults(1)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getCampagneActives(): ?array
    {
        return $this->createQueryBuilder('c')
            ->where("c.active = true")
            ->getQuery()
            ->getResult();
    }
    public function findCampagnActive(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->Where('c.active = :active')
            ->andWhere('c.status = :status')
            ->setParameter('active', true)
            ->setParameter('status', Status::PENDING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findCampagnActiveAndPending(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->Where('c.active = :active')
            ->andWhere('c.status = :status_pending OR c.status = :status_validated')
            ->setParameter('active', true)
            ->setParameter('status_pending', Status::PENDING)
            ->setParameter('status_validated', Status::VALIDATED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function lastCampagne(bool $isOrdinaire): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->where("c.active = false")
            ->andWhere("c.ordinary = :value")
            ->andWhere("c.status = :statut")
            ->setParameter('value', $isOrdinaire)
            ->setParameter('statut', Status::VALIDATE)
            ->orderBy("c.id", "DESC")
            ->setMaxResults(1)
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

    public function checkPersonalInCampagne(Personal $personal): array
    {
        return $this->createQueryBuilder('c')
            ->join("c.personal", 'p')
            ->where("p = :personal")
            ->setParameter('personal', $personal)
            ->getQuery()->getScalarResult();
    }

    /** Retourne la dernière campagne ordinaire */
    public function findLast(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->where('c.ordinary = true')
            ->andWhere('c.status = :status')
            ->setParameter('status', Status::TERMINER)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Retourne la dernière campagne ordinaire */
    public function findLastCampagneForRecap(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->where('c.ordinary = true')
            ->andWhere('c.status IN (:status)')
            ->setParameter('status', [Status::PENDING, Status::VALIDATE])
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Retourne l'avant dernière campagne ordinaire*/
    public function findBeforeLast(): ?Campagne
    {
        return $this->createQueryBuilder('c')
            ->where('c.ordinary = true')
            ->andWhere("c.status = :status")
            ->setParameter('status', Status::TERMINER)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}