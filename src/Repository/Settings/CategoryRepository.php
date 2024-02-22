<?php

namespace App\Repository\Settings;

use App\Entity\Settings\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    private $auth;
    public function __construct(ManagerRegistry $registry, AuthorizationCheckerInterface $auth)
    {
        parent::__construct($registry, Category::class);
        $this->auth = $auth;
    }

    /** @return Category[] */
    public function findCategorie(): array
    {
        return $this->createQueryBuilder('cat')
            ->orderBy('cat.categorySalarie', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findCategorieByEmploye(): QueryBuilder
    {
        if($this->auth->isGranted("ROLE_RH")){
            return $this->createQueryBuilder('cat');
        }
        return $this->createQueryBuilder('cat')
        ->join('cat.categorySalarie', 'categorySalarie') 
        ->Where('categorySalarie.code = :code_employe OR   categorySalarie.code = :code_chauffeur')  
        ->setParameter('code_employe', 'OE') 
        ->setParameter('code_chauffeur', 'CH'); 

    }
}
