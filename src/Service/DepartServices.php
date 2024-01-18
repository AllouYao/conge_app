<?php

namespace App\Service;

use App\Entity\DossierPersonal\Departure;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use JetBrains\PhpStorm\NoReturn;

class DepartServices
{
    private CongeService $congeService;
    private PayrollRepository $payrollRepository;
    const JOUR_CONGE_OUVRABLE = 2.2;
    const JOUR_CONGE_CALANDAIRE = 1.25;
    private CongeRepository $congeRepository;
    private PrimesRepository $primesRepository;
    private UtimePaiementService $utimePaiementService;

    public function __construct(
        CongeService         $congeService,
        PayrollRepository    $payrollRepository,
        CongeRepository      $congeRepository,
        PrimesRepository     $primesRepository,
        UtimePaiementService $utimePaiementService,
    )
    {
        $this->congeService = $congeService;
        $this->payrollRepository = $payrollRepository;
        $this->congeRepository = $congeRepository;
        $this->primesRepository = $primesRepository;
        $this->utimePaiementService = $utimePaiementService;
    }

    public function getAncienneteByDepart(Departure $departure): array
    {
        $dateDepart = $departure->getDate();
        $dateEmbauche = $departure->getPersonal()->getContract()->getDateEmbauche();
        return [
            'ancienneteDays' => (new Carbon($dateDepart))->diff($dateEmbauche)->days, // anciennete in days
            'ancienneteMonth' => ceil((new Carbon($dateDepart))->diff($dateEmbauche)->days / 30), // anciennete in month
            'ancienneteYear' => (new Carbon($dateDepart))->diff($dateEmbauche)->days / 360 // anciennete in year
        ];
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getSalaireGlobalMoyenElement(Departure $departure): array
    {
        $conges = $departure->getPersonal()->getConges();
        $dernierRetour = null;
        $anciennity = $this->getAncienneteByDepart($departure);
        $anciennityYear = $anciennity['ancienneteYear'];
        $salaireBase = (int)$departure->getPersonal()->getSalary()->getBaseAmount();
        $sursalaire = (int)$departure->getPersonal()->getSalary()->getSursalaire();
        $dateEmbauche = $departure->getPersonal()->getContract()->getDateEmbauche();
        $genre = $departure->getPersonal()->getGenre();
        $echelons = $this->congeService->echelonConge($anciennityYear);
        $chargPeapleOfPersonal = $departure->getPersonal()->getChargePeople();
        $dateDepart = $departure->getDate();
        $jourSupp = $this->congeService->suppConger($genre, $chargPeapleOfPersonal, $dateDepart);


        if ($conges) {
            foreach ($conges as $conge) {
                $dernierRetour = $conge?->getDateDernierRetour();
            }
            $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($departure->getPersonal(), $dernierRetour, $dateDepart);
            $moisTravailler = $this->congeService->getWorkMonths($dernierRetour, $dateDepart, $genre, $echelons, $jourSupp);
            $salairePeriodique = $this->payrollRepository->getTotalSalarieBaseAndSursalaire($departure->getPersonal(), $dernierRetour);
        } else {
            $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($departure->getPersonal(), $dateEmbauche, $dateDepart);
            $moisTravailler = $this->congeService->getWorkMonths($dateEmbauche, $dateDepart, $genre, $echelons, $jourSupp);
            $salairePeriodique = $this->payrollRepository->getTotalSalarieBaseAndSursalaire($departure->getPersonal(), $dateDepart);
        }

        $tauxGratification = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
        $gratification = $salaireBase * $tauxGratification * ceil($moisTravailler) / 12;
        $salaireMoyen = ($salaireBrutPeriodique + $gratification) / ceil($moisTravailler);
        $allocationConge = ($salaireMoyen * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * ceil($moisTravailler)) / 30;
        $primeAciennete = $this->utimePaiementService->getAmountAnciennete($departure->getPersonal());
        $preavis = $this->getIndemnitePreavisByDepart($departure);
        $salaireGlobalMoyen = $salairePeriodique + $primeAciennete + $allocationConge + $gratification + $preavis;
        return [
            'Gratification' => $gratification,
            'Allocation_conge' => (int)$allocationConge,
            'Salaire_base' => $salaireBase,
            'Sursalaire' => $sursalaire,
            'Prime_anciennete' => $primeAciennete,
            'Salaire_global_moyen' => (int)$salaireGlobalMoyen,
            'Preavis' => $preavis,
            'retourConge' => $dernierRetour->format('d/m/Y')
        ];

    }

    public function getPreavisByDepart(mixed $anciennete): int
    {
        $preavis = 0;
        if ($anciennete <= 6) {
            $preavis = 1;
        } elseif ($anciennete >= 7 && $anciennete <= 11) {
            $preavis = 2;
        } elseif ($anciennete >= 12 && $anciennete <= 16) {
            $preavis = 3;
        } elseif ($anciennete >= 17) {
            $preavis = 4;
        }
        return $preavis;
    }

    public function getIndemnitePreavisByDepart(Departure $departure): int|float
    {
        $salaryEntity = $departure->getPersonal()->getSalary();
        $salaireBase = $this->utimePaiementService->getAmountSalaireBrutAndImposable($departure->getPersonal());
        $sursalaire = $salaryEntity->getSursalaire();
        $primeAnciennete = $this->utimePaiementService->getAmountAnciennete($departure->getPersonal());
        $primeIndemnite = $salaryEntity->getTotalAutrePrimes();
        $primeTransport = $salaryEntity->getPrimeTransport();
        $transportExonere = $this->utimePaiementService->getPrimeTransportLegal();
        $salaireBrut = $salaireBase['salaire_categoriel'] + $sursalaire + $primeAnciennete + $primeIndemnite + $primeTransport;
        $plafondIndemniteTheorique = ($salaireBrut - $transportExonere) * (10 / 100);
        $anciennete = $this->getAncienneteByDepart($departure);
        $dureePreavis = $this->getPreavisByDepart($anciennete['ancienneteYear']);
        $brutImposable = $salaireBrut - $plafondIndemniteTheorique;
        $indemnitePreavis = $brutImposable * $dureePreavis;
        if ($plafondIndemniteTheorique > $indemnitePreavis) {
            $indemnitePreavis = $plafondIndemniteTheorique;
        }
        return (int)$indemnitePreavis;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getIndemniteLicenciementByDepart(Departure $departure): int|float
    {
        $element = $this->getSalaireGlobalMoyenElement($departure);
        $salaireGlobalMoyen = $element['Salaire_global_moyen'];
        $indemniteLicenciement = null;
        //
        $salaryEntity = $departure->getPersonal()->getSalary();
        $salaireBase = $this->utimePaiementService->getAmountSalaireBrutAndImposable($departure->getPersonal());
        $sursalaire = $salaryEntity->getSursalaire();
        $primeAnciennete = $this->utimePaiementService->getAmountAnciennete($departure->getPersonal());
        $allocationConge = $element['Allocation_conge'];
        $gratifProrata = $element['Gratification'];

        $preavis = $this->getIndemnitePreavisByDepart($departure);

        $anciennity = $this->getAncienneteByDepart($departure);
        $anciennityYear = $anciennity['ancienneteYear'];

        if ($anciennityYear < 1) {
            $indemniteLicenciement = 0;
        } elseif ($anciennityYear <= 5) {
            $indemniteLicenciement = $anciennityYear * (($salaireGlobalMoyen * 30) / 100);
        } elseif ($anciennityYear >= 6 && $anciennityYear <= 10) {
            $indemniteLicenciement =
                5 * (($salaireGlobalMoyen * 30) / 100) + ($anciennityYear - 5) * (($salaireGlobalMoyen * 35) / 100);
        } elseif ($anciennityYear > 10) {
            $indemniteLicenciement =
                5 * (($salaireGlobalMoyen * 30) / 100) + 5 * (($salaireGlobalMoyen * 35) / 100) + ($anciennityYear - 10)
                * (($salaireGlobalMoyen * 40) / 100);
        }

        return $indemniteLicenciement;
    }


    /**
     * @param Departure $departure
     * @return void
     * @throws NonUniqueResultException|NoResultException
     */
    #[NoReturn] public function rightAndIndemnityByDeparture(Departure $departure): void
    {
        $reason = $departure->getReason();
        $salaireDue = $departure->getPersonal()->getSalary()->getBrutAmount();
        $elements = $this->getSalaireGlobalMoyenElement($departure);
        $indemniteLicenciement = $this->getIndemniteLicenciementByDepart($departure);
        $departure
            ->setSalaryDue($salaireDue)
            ->setGratification($elements['Gratification'])
            ->setCongeAmount($elements['Allocation_conge']);
        if ($reason === Status::RETRAITE) {
            $departure->setDissmissalAmount($indemniteLicenciement);
        } elseif ($reason === Status::MALADIE) {
            $departure
                ->setNoticeAmount($elements['Preavis'])
                ->setDissmissalAmount($indemniteLicenciement);
        } elseif ($reason === Status::DECES) {
            $departure->setDissmissalAmount($indemniteLicenciement);
        } elseif ($reason === Status::LICENCIEMENT_COLLECTIF) {
            $departure
                ->setNoticeAmount($elements['Preavis'])
                ->setDissmissalAmount($indemniteLicenciement);
        } elseif ($reason === Status::LICENCIEMENT_FAIT_EMPLOYEUR) {
            $active = $this->congeRepository->getCongeInDepart($departure->getPersonal());

            $dateDepart = $departure->getDate();
            $departConge = $active?->getDateDepart();
            $retourConge = $active?->getDateRetour();
            $datePrecedent = $departConge->modify('-15 days');
            $dateSuivant = $retourConge->modify('+15 days');
            $condition1 = $dateDepart >= $datePrecedent && $dateDepart <= $departConge;
            $condition2 = $dateDepart > $retourConge && $dateDepart <= $dateSuivant;
            $supplement_indemnite = 0;
            if ($active->isIsConge() === true || $condition1 || $condition2) {
                $supplement_indemnite = $salaireDue * 2;
            }
            $departure
                ->setNoticeAmount($elements['Preavis'] + $supplement_indemnite)
                ->setDissmissalAmount($indemniteLicenciement);
        }
    }
}