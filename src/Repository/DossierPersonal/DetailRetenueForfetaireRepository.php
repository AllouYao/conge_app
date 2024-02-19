<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\DetailRetenueForfetaire;
use App\Entity\DossierPersonal\Personal;
use App\Entity\DossierPersonal\RetenueForfetaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetailRetenueForfetaire>
 *
 * @method DetailRetenueForfetaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetailRetenueForfetaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetailRetenueForfetaire[]    findAll()
 * @method DetailRetenueForfetaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetailRetenueForfetaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetailRetenueForfetaire::class);
    }

    public function findRetenueForfetaire(Personal $personal, RetenueForfetaire $retenueForfetaire): ?DetailRetenueForfetaire
    {
        $qb = $this->createQueryBuilder('d');
        $qb
            ->join('d.salary', 'salary')
            ->where('salary.personal =:personal')
            ->andWhere('d.retenuForfetaire =:retenu_forfetaire')
            ->setParameters([
                'personal' => $personal,
                'retenu_forfetaire' => $retenueForfetaire
            ]);
        return $qb->getQuery()->getOneOrNullResult();
    }
}
