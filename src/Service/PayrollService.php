<?php

namespace App\Service;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Entity\Paiement\Payroll;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class PayrollService
{
    private EntityManagerInterface $manager;
    private ChargeEmployeurRepository $chargeEmployeurRepository;
    private ChargePersonalsRepository $chargePersonalsRepository;


    public function __construct(
        EntityManagerInterface    $entityManager,
        ChargeEmployeurRepository $chargeEmployeurRepository,
        ChargePersonalsRepository $chargePersonalsRepository
    )
    {
        $this->manager = $entityManager;
        $this->chargeEmployeurRepository = $chargeEmployeurRepository;
        $this->chargePersonalsRepository = $chargePersonalsRepository;
    }

    /**
     * @param Personal $personal
     * @param Campagne $campagne
     * @throws NonUniqueResultException
     */
    public function setPayroll(Personal $personal, Campagne $campagne): void
    {
        /**
         * Récupération des éléménts de salaire
         */
        $salary = $personal->getSalary();
        $baseSalaire = $salary->getBaseAmount();
        $sursalaire = $salary->getSursalaire();
        $brutAmount = $salary->getBrutAmount();
        $imposableAmount = $salary->getBrutImposable();

        /**
         * charge du personnel et retenue fixcal
         */
        $chargePersonal = $this->chargePersonalsRepository->findOneBy(['personal' => $personal]);
        $nombrePart = $chargePersonal->getNumPart();
        $salaryIts = $chargePersonal->getAmountIts();
        $salaryCnps = $chargePersonal->getAmountCNPS();
        $salaryCmu = $chargePersonal->getAmountCMU();
        $fiscalAmount = $chargePersonal->getAmountTotalChargePersonal();

        /**
         * net à payer, total retenue, indemnité de transport et assurance santé du personnel
         */
        $salarySante = 0;
        $revenueAmount = $fiscalAmount + $salarySante;
        $salaryTransport = $salary->getPrimeTransport();
        $netPayer = $imposableAmount + $revenueAmount + $salaryTransport;

        /**
         * charge de l'employeur et retenue fixcal
         */
        $chargeEmployeur = $this->chargeEmployeurRepository->findOneBy(['personal' => $personal]);
        $employeurIS = $chargeEmployeur?->getAmountIS();
        $employeurFDFP = $chargeEmployeur?->getAmountFDFP();
        $employeurCMU = $chargeEmployeur?->getAmountCMU();
        $employeurCR = $chargeEmployeur?->getAmountCR();
        $employeurPF = $chargeEmployeur?->getAmountPF();
        $employeurAT = $chargeEmployeur?->getAmountAT();
        $employeurCNPS = $chargeEmployeur?->getTotalRetenuCNPS();
        $fixcalAmountEmployeur = $chargeEmployeur?->getTotalChargeEmployeur();
        $employeurAssuranceSante = 0;

        /**
         * la masse salariale
         */
        $masseSalaries = $imposableAmount + $salaryTransport + $fixcalAmountEmployeur + $employeurAssuranceSante;

        /**
         * Enregistrement du livre de paie
         */
        $payroll = (new Payroll())
            ->setPersonal($personal)
            ->setCampagne($campagne)
            ->setNumberPart($nombrePart)
            ->setBaseAmount($baseSalaire)
            ->setSursalaire($sursalaire)
            ->setBrutAmount($brutAmount)
            ->setImposableAmount($imposableAmount)
            ->setSalaryIts($salaryIts)
            ->setSalaryCmu($salaryCmu)
            ->setSalaryCnps($salaryCnps)
            ->setFixcalAmount($fiscalAmount)
            ->setSalarySante($salarySante)
            ->setSalaryTransport($salaryTransport)
            ->setNetPayer($netPayer)
            ->setEmployeurIs($employeurIS)
            ->setEmployeurCmu($employeurCMU)
            ->setEmployeurSante($employeurAssuranceSante)
            ->setFixcalAmountEmployeur($fixcalAmountEmployeur)
            ->setEmployeurFdfp($employeurFDFP)
            ->setEmployeurPf($employeurPF)
            ->setEmployeurCr($employeurCR)
            ->setEmployeurAt($employeurAT)
            ->setEmployeurCnps($employeurCNPS)
            ->setMasseSalary($masseSalaries);
        $this->manager->persist($payroll);
    }
}