<?php

namespace App\Service\Personal;

use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\DetailPrimeSalaryRepository;
use App\Repository\DossierPersonal\DetailSalaryRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;

class PrimeService
{
    public function __construct(
        private readonly PrimesRepository            $primesRepository,
        private readonly DetailSalaryRepository      $detailSalaryRepository,
        private readonly DetailPrimeSalaryRepository $detailPrimeSalaryRepository
    )
    {
    }

    /** Montant de la prime de panier du personal peut être utiliser à n'importe quel periode */
    public function getPrimePanier(Personal $personal): float|int
    {
        $primePanier = $this->primesRepository->findOneBy(['code' => Status::PRIME_PANIER]);
        $amountPanier = $this->detailSalaryRepository->findPrime($personal, $primePanier);
        return (int)$amountPanier?->getAmountPrime();
    }

    /** Montant de la prime de salissure du personal peut être utiliser à n'importe quel periode */
    public function getPrimeSalissure(Personal $personal): float|int
    {
        $primeSalissure = $this->primesRepository->findOneBy(['code' => Status::PRIME_SALISSURE]);
        $amountSalissure = $this->detailSalaryRepository->findPrime($personal, $primeSalissure);
        return (int)$amountSalissure?->getAmountPrime();
    }

    /** Montant la prime de tenue de travail du personal peut être utiliser à n'importe quel periode */
    public function getPrimeTT(Personal $personal): float|int
    {
        $primeTT = $this->primesRepository->findOneBy(['code' => Status::PRIME_TENUE_TRAVAIL]);
        $amountTT = $this->detailSalaryRepository->findPrime($personal, $primeTT);
        return (int)$amountTT?->getAmountPrime();
    }

    /** Montant la prime d'outillage du personal peut être utiliser à n'importe quel periode */
    public function getPrimeOutil(Personal $personal): float|int
    {
        $primeOutil = $this->primesRepository->findOneBy(['code' => Status::PRIME_OUTILLAGE]);
        $amountOutil = $this->detailSalaryRepository->findPrime($personal, $primeOutil);
        return (int)$amountOutil?->getAmountPrime();
    }

    /** Montant la prime de rendement du personal peut être utiliser à n'importe quel periode */
    public function getPrimeRendement(Personal $personal): float|int
    {
        $primeRendement = $this->primesRepository->findOneBy(['code' => Status::PRIME_RENDEMENT]);
        $amountRendement = $this->detailSalaryRepository->findPrime($personal, $primeRendement);
        return (int)$amountRendement?->getAmountPrime();
    }

    /** Montant la prime de fonction du personal peut être utiliser à n'importe quel periode */
    public function getPrimeFonction(Personal $personal): float|int
    {
        $primeFonction = $this->primesRepository->findOneBy(['code' => Status::PRIME_FONCTION]);
        $amountFonction = $this->detailPrimeSalaryRepository->findPrimes($personal, $primeFonction);
        return (int)$amountFonction?->getAmount();
    }

    /** Montant la prime de logement du personal peut être utiliser à n'importe quel periode */
    public function getPrimeLogement(Personal $personal): float|int
    {
        $primeLogement = $this->primesRepository->findOneBy(['code' => Status::PRIME_LOGEMENT]);
        $amountLogement = $this->detailPrimeSalaryRepository->findPrimes($personal, $primeLogement);
        return (int)$amountLogement?->getAmount();
    }

    /** Montant l'indemnite de fonction du personal peut être utiliser à n'importe quel periode */
    public function getIndemniteFonction(Personal $personal): float|int
    {
        $indemniteFonction = $this->primesRepository->findOneBy(['code' => Status::INDEMNITE_FONCTION]);
        $amountIndemFonction = $this->detailPrimeSalaryRepository->findPrimes($personal, $indemniteFonction);
        return (int)$amountIndemFonction?->getAmount();
    }

    /** Montant l'indemnite de logement du personal peut être utiliser à n'importe quel periode */
    public function getIndemniteLogement(Personal $personal): float|int
    {
        $indemniteLogement = $this->primesRepository->findOneBy(['code' => Status::INDEMNITE_LOGEMENTS]);
        $amountIndemLogement = $this->detailPrimeSalaryRepository->findPrimes($personal, $indemniteLogement);
        return (int)$amountIndemLogement?->getAmount();
    }

    /** Montant prime de transport non du personal peut être utiliser à n'importe quel periode */
    public function getPrimeTransportLegal(): float|int
    {
        $primeTransport = $this->primesRepository->findOneBy(['code' => Status::PRIME_TRANSPORT]);
        return (int)$primeTransport->getTaux();
    }
}