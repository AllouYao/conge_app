<?php

namespace App\Service;

use App\Entity\DossierPersonal\Departure;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use JetBrains\PhpStorm\NoReturn;

class DepartServices
{
    private EtatService $etatService;
    private CongeService $congeService;
    private PayrollRepository $payrollRepository;
    const JOUR_CONGE_OUVRABLE = 2.2;
    const JOUR_CONGE_CALANDAIRE = 1.25;
    private CongeRepository $congeRepository;

    public function __construct(
        EtatService       $etatService,
        CongeService      $congeService,
        PayrollRepository $payrollRepository,
        CongeRepository   $congeRepository
    )
    {
        $this->etatService = $etatService;
        $this->congeService = $congeService;
        $this->payrollRepository = $payrollRepository;
        $this->congeRepository = $congeRepository;
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

    public function getSalaireGlobalMoyenElement(Departure $departure): array
    {
        $anciennity = $this->getAncienneteByDepart($departure);
        $anciennityYear = $anciennity['ancienneteYear'];
        $salaireBase = $departure->getPersonal()->getSalary()->getBaseAmount();
        $sursalaire = $departure->getPersonal()->getSalary()->getSursalaire();
        $dateEmbauche = $departure->getPersonal()->getContract()->getDateEmbauche();
        $dateDepart = $departure->getDate();
        $anciennityDays = (new Carbon($dateDepart))->diff($dateEmbauche);
        $genre = $departure->getPersonal()->getGenre();
        $echelons = $this->congeService->echelonConge($anciennityYear);
        $chargPeapleOfPersonal = $departure->getPersonal()->getChargePeople();
        $jourSupp = $this->congeService->suppConger($genre, $chargPeapleOfPersonal, $dateDepart);
        $moisTravailler = $this->congeService->getWorkMonths($dateEmbauche, $dateDepart, $genre, $echelons, $jourSupp);
        $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($departure->getPersonal(), $dateEmbauche, $dateDepart);
        $gratification = $this->etatService->getGratifications($anciennityDays, $salaireBase);
        $salaireMoyen = (($salaireBrutPeriodique + $gratification)) / $moisTravailler;
        $allocationConge = ($salaireMoyen * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $moisTravailler) / 30;
        $primeAciennete = $this->etatService->getPrimeAnciennete($departure->getPersonal()->getId());
        $preavis = $this->getIndemnitePreavisByDepart($departure);
        $salairePeriodique = $this->payrollRepository->getTotalSalarieBaseAndSursalaire($departure->getPersonal(), $dateEmbauche, $dateDepart);
        $salaireGlobalMoyen = $salairePeriodique + $primeAciennete + $allocationConge + $gratification + $preavis;
        return [
            'Gratification' => $gratification,
            'Allocation_conge' => $allocationConge,
            'Salaire_base' => $salaireBase,
            'Sursalaire' => $sursalaire,
            'Prime_anciennete' => $primeAciennete,
            'Salaire_global_moyen' => (int)$salaireGlobalMoyen,
            'Preavis' => $preavis
        ];

    }

    public function getCongeElementInDepart(Departure $departure): array
    {
        $congeElement = [];
        $personal = $departure->getPersonal();
        foreach ($personal->getConges()->toArray() as $conge) {
            $congeElement = [
                'salaireMoyen' => $conge?->getSalaireMoyen(),
                'gratification' => $conge?->getGratification(),
                'dateDernierConge' => $conge?->getDateDernierRetour(),
                'indemniteConge' => $conge?->getAllocationConge(),
            ];
        }
        return $congeElement;
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
        $salaireBrut = $departure->getPersonal()->getSalary()->getBrutAmount();
        $anciennete = $this->getAncienneteByDepart($departure);
        $dureePreavis = $this->getPreavisByDepart($anciennete['ancienneteYear']);
        $indemnitePreavis = $salaireBrut * $dureePreavis;

        return (int)$indemnitePreavis;
    }

    public function getIndemniteLicenciementByDepart(Departure $departure): int|float
    {
        //$anciennete = $this->getAncienneteByDepart($departure);
        //$ancienneteYear = $anciennete['ancienneteYear'];

        $element = $this->getSalaireGlobalMoyenElement($departure);
        $salaireGlobalMoyen = $element['Salaire_global_moyen'];
        $indemniteLicenciement = null;

        $olderPersonal = $departure->getPersonal()->getOlder();

        if ($olderPersonal < 1) {
            $indemniteLicenciement = 0;
        } elseif ($olderPersonal <= 5) {
            $indemniteLicenciement = $olderPersonal * (($salaireGlobalMoyen * 30) / 100);
        } elseif ($olderPersonal >= 6 && $olderPersonal <= 10) {
            $indemniteLicenciement =
                5 * (($salaireGlobalMoyen * 30) / 100) + ($olderPersonal - 5) * (($salaireGlobalMoyen * 35) / 100);
        } elseif ($olderPersonal > 10) {
            $indemniteLicenciement =
                5 * (($salaireGlobalMoyen * 30) / 100) + 5 * (($salaireGlobalMoyen * 35) / 100) + ($olderPersonal - 10)
                * (($salaireGlobalMoyen * 40) / 100);
        }
        return $indemniteLicenciement;
    }


    /**
     * @param Departure $departure
     * @return void
     * @throws NonUniqueResultException
     */
    #[NoReturn] public function rightAndIndemnityByDeparture(Departure $departure): void
    {
        $reason = $departure->getReason();
        $salaireDue = $departure->getPersonal()->getSalary()->getBrutAmount();
        $elements = $this->getSalaireGlobalMoyenElement($departure);
        $indemniteLicenciement = $this->getIndemniteLicenciementByDepart($departure);
        if ($reason === Status::RETRAITE) {
            $departure
                ->setSalaryDue($salaireDue)
                ->setGratification($elements['Gratification'])
                ->setCongeAmount($elements['Allocation_conge'])
                ->setDissmissalAmount($indemniteLicenciement);
        } elseif ($reason === Status::ABANDON_DE_POST) {
            $departure
                ->setSalaryDue($salaireDue)
                ->setGratification($elements['Gratification'])
                ->setCongeAmount($elements['Allocation_conge']);

        } elseif ($reason === Status::MALADIE) {
            $departure
                ->setSalaryDue($salaireDue)
                ->setGratification($elements['Gratification'])
                ->setCongeAmount($elements['Allocation_conge'])
                ->setNoticeAmount($elements['Preavis'])
                ->setDissmissalAmount($indemniteLicenciement);
        } elseif ($reason === Status::DEMISSION) {
            $departure
                ->setSalaryDue($salaireDue)
                ->setGratification($elements['Gratification'])
                ->setCongeAmount($elements['Allocation_conge']);
        } elseif ($reason === Status::DECES) {
            $departure
                ->setSalaryDue($salaireDue)
                ->setGratification($elements['Gratification'])
                ->setCongeAmount($elements['Allocation_conge'])
                ->setDissmissalAmount($indemniteLicenciement);
        } elseif ($reason === Status::LICENCIEMENT_COLLECTIF) {
            $departure
                ->setSalaryDue($salaireDue)
                ->setGratification($elements['Gratification'])
                ->setCongeAmount($elements['Allocation_conge'])
                ->setNoticeAmount($elements['Preavis'])
                ->setDissmissalAmount($indemniteLicenciement);
        } elseif ($reason === Status::LICENCIEMENT_FAUTE_LOURDE) {
            $departure
                ->setSalaryDue($salaireDue)
                ->setGratification($elements['Gratification'])
                ->setCongeAmount($elements['Allocation_conge']);
        } elseif ($reason === Status::LICENCIEMENT_FAIT_EMPLOYEUR) {
            $active = $this->congeRepository->active($departure->getPersonal());
            $dateDepart = $departure->getDate();
            $departConge = $active->getDateDepart();
            $retourConge = $active->getDateRetour();
            $datePrecedent = $departConge->modify('-15 days');
            $dateSuivant = $retourConge->modify('+15 days');
            $condition1 = $dateDepart >= $datePrecedent && $dateDepart <= $departConge;
            $condition2 = $dateDepart > $retourConge && $dateDepart <= $dateSuivant;
            $supplement_indemnite = 0;
            if ($active->isIsConge() === true || $condition1 || $condition2) {
                $supplement_indemnite = $salaireDue * 2;
            }
            $departure
                ->setSalaryDue($salaireDue)
                ->setGratification($elements['Gratification'])
                ->setCongeAmount($elements['Allocation_conge'])
                ->setNoticeAmount($elements['Preavis'] + $supplement_indemnite)
                ->setDissmissalAmount($indemniteLicenciement);
        }

        dd($departure);
    }
}