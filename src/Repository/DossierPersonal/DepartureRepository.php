<?php

namespace App\Repository\DossierPersonal;

use App\Entity\DossierPersonal\Departure;
use App\Entity\DossierPersonal\Personal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Departure>
 *
 * @method Departure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Departure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Departure[]    findAll()
 * @method Departure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepartureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Departure::class);
    }

    /**
     * @param $typeDepart
     * @return Departure[]
     */
    public function getDepartureByDate($typeDepart): ?array
    {
        $result = $this->createQueryBuilder('departure')
            ->select([
                'departure.id',
                'p.matricule',
                'p.firstName',
                'p.lastName',
                'p.older',
                'p.refCNPS',
                'p.modePaiement',
                'categorie.intitule',
                'contract.dateEmbauche as date_embauche',
                'contract.typeContrat as type_contrat',
                'job.name as job_name',
                'workplace.name as workplace_name',
                'salary.smig',
                'departure.date as departure_date',
                'departure.dayOfPresence as day_of_presence',
                'departure.salaryDue as salaire_presence',
                'departure.gratification as gratification_prorata',
                'departure.dateRetourConge as date_retour_conge',
                'departure.periodeReferences as periode_references',
                'departure.congesOuvrable as conges_ouvrable',
                'departure.cumulSalaire as salaire_moyen_conges',
                'departure.congeAmount as conges_amount',
                'departure.noticeAmount as indemnite_preavis',
                'departure.globalMoyen as salaire_global_moyen',
                'departure.dissmissalAmount as indemnite_licenciement',
                'departure.amountLcmtImposable as quotite_imposable',
                'departure.amountLcmtNoImposable as quotite_non_imposable',
                'departure.totalIndemniteImposable as total_indemnite_imposable',
                'departure.totatChargePersonal as total_charge_personal',
                'departure.netPayer as net_payer_indemnite',
                'departure.uuid as uuid',
                'departure.reason',
                'departure.reasonCode as type_depart',
                'departure.fraisFuneraire as frais_funeraire',
                'departure.nbPart as nombre_part',
                'departure.createdAt',
                'departure.totalChargeEmployer as total_charge_employer',
                'departure.amountCmuE',
                'departure.amountCmu',
                'departure.amountfpc',
                'departure.amountFpcYear',
                'departure.amountTa',
                'departure.amountIs',
                'departure.amountAt',
                'departure.amountPf',
                'departure.amountCr',
                'departure.amountCnps',
                'departure.impotNet',
                //'SUM(departure.salaryDue + departure.gratification + departure.congeAmount + departure.noticeAmount + departure.dissmissalAmount) as indemnite_brut'
            ])
            ->join('departure.personal', 'p')
            ->join('p.categorie', 'categorie')
            ->join('p.contract', 'contract')
            ->join('p.job', 'job')
            ->join('p.workplace', 'workplace')
            ->join('p.salary', 'salary')
            ->andWhere('departure.reason = :typeDepart')
            ->setParameter('typeDepart', $typeDepart)
            ->orderBy('departure.date', 'ASC')
            ->getQuery()
            ->getResult();
        return !empty($result) ? $result : null;
    }

    /** Obtenir le depart qui est entre les 15 jours qui suivent la date de retour en congé */
    public function get15DaysAfterDate(\DateTime $date): ?Departure
    {
        $startDate = clone $date;

        $endDate = clone $date;
        $endDate->modify('+15 days');

        return $this->createQueryBuilder('d')
            ->where('c.dateDernierRetour > :startDate')
            ->andWhere('c.dateDernierRetour <= :endDate')
            ->andWhere('d.date BETWEEN :')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function get15DaysAftersDate(mixed $date, Personal $personal): ?Departure
    {
        $startDate = $date;
        $nextFifteenDays = [];
        for ($i = 1; $i <= 15; $i++) {
            $date = clone $startDate;
            $date->modify("+$i days");
            $nextFifteenDays[] = $date;
        }
        return $this->createQueryBuilder('d')
            ->join('d.personal', 'personal')
            ->join('personal.conges', 'c')
            ->where('d.personal = :d_personal')
            ->andWhere('d.date BETWEEN :start AND :end')
            ->setParameter('d_personal', $personal)
            ->setParameter('start', $nextFifteenDays[14])
            ->setParameter('end', $nextFifteenDays[0])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
