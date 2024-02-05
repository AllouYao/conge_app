Pour activer mon server Mysql:

- docker compose up -d
- #C9971C
- sudo systemctl restart cron.service
- crontab -e
- symfony console cron:list
- symfony console cron:run
- symfony console cron:start
- php bin/console schedule:list
- php bin/console schedule:list --detail


    public function getIndemnitePreavisByDepart(Departure $departure): int|float
    {
    $salaryEntity = $departure->getPersonal()->getSalary();
    $salaireBase = $this->utimePaiementService->getAmountSalaireBrutAndImposable($departure->getPersonal());
    $sursalaire = $salaryEntity->getSursalaire();
    $primeAnciennete = $this->utimePaiementService->getAmountAnciennete($departure->getPersonal());
    $primeIndemnite = $salaryEntity->getTotalAutrePrimes();
    $primeTransport = $salaryEntity->getPrimeTransport();
    $transportExonere = $this->utimePaiementService->getPrimeTransportLegal();
    $salaireBrut = $salaireBase['salaire_categoriel'] + $sursalaire + $primeAnciennete + $primeIndemnite +
    $primeTransport;
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
        public function getIndemniteLicenciementByDepart(Departure $departure): int|float|null
        {
        $element = $this->getSalaireGlobalMoyenElement($departure);
        $salaireGlobalMoyen = $element['Salaire_global_moyen'];
        $indemniteLicenciement = null;

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
    $salaireDue = $this->payrollRepository->findLastPayroll(true)->getNetPayer();
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
    $departure
    ->setDissmissalAmount($indemniteLicenciement);
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

