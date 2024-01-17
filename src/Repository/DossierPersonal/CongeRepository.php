<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Conge;
use App\Entity\DossierPersonal\Personal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conge>
 *
 * @method Conge|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conge|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conge[]    findAll()
 * @method Conge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conge::class);
    }

    /**
     * @return Conge[]
     */
    public function findConge(string $typeConges): array
    {
        return $this->createQueryBuilder('co')
            ->select([
                'p.firstName as nom',
                'p.lastName as prenoms',
                'co.dateRetour as retour',
                'co.dateDepart as depart',
                'co.salaireMoyen as salaire_moyen',
                'co.allocationConge as allocation_conge',
                'co.isConge as en_conge',
                'co.dateDernierRetour as dernier_retour',
                'co.uuid',
                'co.totalDays',
                'co.days',
                'co.remainingVacation',
            ])
            ->join('co.personal', 'p')
            ->where('co.personal is not null')
            ->andWhere('co.typeConge = :type_conge')
            ->setParameter('type_conge', $typeConges)
            ->orderBy('co.dateDernierRetour', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getCongeInDepart(Personal $personal): ?Conge
    {
        return $this->createQueryBuilder('co')
            ->where('co.personal = :personal')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->orderBy('co.id', 'DESC')
            ->getQuery()->getOneOrNullResult();
    }

    public function getLastCongeByID(int $personal): ?Conge
    {
        return $this->createQueryBuilder('co')
            ->join('co.personal', 'personal')
            ->where('personal.id = :personal')
            ->andWhere('co.isConge = false')
            ->setMaxResults(1)
            ->setParameter('personal', $personal)
            ->orderBy('co.id', 'DESC')
            ->getQuery()->getOneOrNullResult();
    }
    /**
     * @throws NonUniqueResultException
     */
    public function active(Personal $personal): ?Conge
    {
        return $this->createQueryBuilder('co')
            ->where('co.personal = :personal')
            ->andWhere("co.isConge = true")
            ->setParameter('personal', $personal)
            ->setMaxResults(1)
            ->orderBy('co.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function activeForAll(): ?array
    {
        return $this->createQueryBuilder('co')
            ->join('co.personal', 'personal')
            ->andWhere("co.isConge = true")
            ->orderBy('co.id', 'DESC')
            ->getQuery()
            ->getResult();
    }


}
