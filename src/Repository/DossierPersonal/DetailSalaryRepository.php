<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\DetailSalary;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Settings\Primes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetailSalary>
 *
 * @method DetailSalary|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetailSalary|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetailSalary[]    findAll()
 * @method DetailSalary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetailSalaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetailSalary::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findPrimeBySalary(Personal $personal, Primes $primes)
    {
        $qb = $this->createQueryBuilder('d');
        $qb
            ->select('d.amountPrime')
            ->join('d.salary', 'salary')
            ->where('salary.personal =:personal')
            ->andWhere('d.prime =:prime')
            ->setParameters([
                'personal' => $personal,
                'prime' => $primes
            ]);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findPrime(Personal $personal, Primes $primes): ?DetailSalary
    {
        $qb = $this->createQueryBuilder('d');
        $qb
            //->select('d.amountPrime')
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
