<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\DetailPrimeSalary;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Settings\Primes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetailPrimeSalary>
 *
 * @method DetailPrimeSalary|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetailPrimeSalary|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetailPrimeSalary[]    findAll()
 * @method DetailPrimeSalary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetailPrimeSalaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetailPrimeSalary::class);
    }

    public function findPrimeBySalaries(Personal $personal, Primes $primes)
    {
        $qb = $this->createQueryBuilder('d');
        $qb
            ->select('d.amount')
            ->join('d.salary', 'salary')
            ->where('salary.personal =:personal')
            ->andWhere('d.prime =:prime')
            ->setParameters([
                'personal' => $personal,
                'prime' => $primes
            ]);
        return $qb->getQuery()->getOneOrNullResult();
    }
}
