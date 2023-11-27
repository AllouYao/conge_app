<?php

namespace App\Repository\Paiement;

use App\Entity\Paiement\Payroll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payroll>
 *
 * @method Payroll|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payroll|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payroll[]    findAll()
 * @method Payroll[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PayrollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payroll::class);
    }

//    /**
//     * @return Payroll[] Returns an array of Payroll objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Payroll
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findPayrollByCampaign(bool $campagne): array
    {
        return $this->createQueryBuilder('pr')
            ->select([
                'pr.id',
                'p.firstName as first_name',
                'p.lastName as last_name',
                'cy.intitule as categories_name',
                'pr.numberPart as nombre_part',
                'pr.baseAmount as base_salary',
                'pr.sursalaire as sursalaire',
                'pr.brutAmount as brut_salary',
                'pr.imposableAmount as imposable_salary',
                'pr.salaryIts as salary_its',
                'pr.salaryCnps as salary_cnps',
                'pr.salaryCmu as salary_cmu',
                'pr.salarySante as salary_assurance',
                'pr.salaryTransport as salary_transport',
                'pr.fixcalAmount as montant_fixcal_salary',
                'pr.netPayer as net_payer',
                'pr.employeurIs as employeur_is',
                'pr.employeurFdfp as employeur_fdfp',
                'pr.employeurCmu as employeur_cmu',
                'pr.employeurPf as employeur_pf',
                'pr.employeurAt as employeur_at',
                'pr.employeurCnps as employeur_cnps',
                'pr.employeurCr as employeur_cr',
                'pr.employeurSante as employeur_assurance',
                'pr.fixcalAmountEmployeur as fixcal_amount_employeur',
                'pr.masseSalary as masse_salary',
                'c.createdAt as debut'
            ])
            ->join('pr.personal', 'p')
            ->join('p.categorie', 'cy')
            ->join('pr.campagne', 'c')
            ->andWhere('c.active = :active')
            ->setParameter('active', $campagne)
            ->getQuery()->getResult();
    }

}
