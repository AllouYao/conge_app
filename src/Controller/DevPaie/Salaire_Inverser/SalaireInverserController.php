<?php

namespace App\Controller\DevPaie\Salaire_Inverser;

use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Settings\CategoryRepository;
use App\Repository\Settings\SmigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/salaire_inverser', name: 'salaire_inverse_')]
class SalaireInverserController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository       $categoryRepository,
        private readonly CategoryChargeRepository $categoryChargeRepository,
        private readonly SmigRepository           $smigRepository
    )
    {
    }

    #[Route('/inverse_net', name: 'net_inverser', methods: ['POST', 'GET'])]
    public function makeInverseSalary(Request $request): Response
    {

        $impotBrut = $creditImpot = $impotNet = $amountCnps = $salaireBrut = $sursalaire = null;
        // $salaireNet = $nbPart = $transportLegal = $categorySalary = $amountCategoriel = $amountPrime = $assurance
        // Options pour le champ prime_juridique
        $primes = [
            ['id' => 1, 'value' => 1298, 'label' => 'Prime de panier'],
            ['id' => 2, 'value' => 5625, 'label' => 'Prime de salissure'],
            ['id' => 3, 'value' => 3029, 'label' => 'Prime de tenue travail'],
            ['id' => 4, 'value' => 4237, 'label' => 'Prime de outillage']
        ];
        $selectionsPrime = [];
        if ($request->isMethod('POST')) {
            $salaireNet = (int)$request->get('salaire_net');
            $nbPart = (double)$request->get('nombre_part');
            $transportLegal = (int)$request->get('transport_legal');
            $categorySalary = $request->get('categorie_salarial');
            $amountCategoriel = (int)$this->categoryRepository->findOneBy(['id' => (int)$categorySalary])->getAmount();
            $selectionsPrime = (array) $request->get('prime_juridique');
            $amountPrime = 0;
            foreach ($selectionsPrime as $selection) {
                $amountPrime += (int)$selection;
            }
            $assurance = (int)$request->get('assurance');

            // calculer l'ITS net la cnps
            $impotBrut = $this->amountImpotBrutInverse($salaireNet);
            $creditImpot = $this->amountCreditImpotInverse($nbPart);
            $impotNet = $impotBrut - $creditImpot;
            if ($impotNet < 0) {
                $impotNet = 0;
            }
            $amountCnps = $this->amountCNPSInverse($salaireNet);

            // Determination du brut inverser et le sursalaire
            $salaireBrut = $salaireNet - $transportLegal - $amountPrime + $impotNet + $amountCnps + $assurance;
            $sursalaire = $salaireBrut > $amountCategoriel ? $salaireBrut - $amountCategoriel : 0;


            $smig = $this->smigRepository->active();
            if ($salaireBrut < $smig->getAmount()) {
                flash()->addInfo('le salaire brut ne peut être inférieur au salaire catégoriel. Veuillez faire une autre manipulation merci !');
                return $this->redirectToRoute('salaire_inverse_net_inverser', [
                    'salaire_net' => $salaireNet,
                    'nombre_part' => $nbPart,
                    'transport_legal' => $transportLegal,
                    'categorie_salaire' => $categorySalary,
                    'salaire_catégoriel' => $amountCategoriel,
                    'prime_amount' => $amountPrime,
                    'assurance' => $assurance,
                    'impot_brut' => $impotBrut,
                    'credit_impot' => $creditImpot,
                    'impot_net' => $impotNet,
                    'cnps' => $amountCnps,
                    'amount_brut' => $salaireBrut,
                    'sursalaire' => $sursalaire
                ]);
            } else {
                flash()->addSuccess('Salaire brut obtenue avec sussès .');
                return $this->redirectToRoute('salaire_inverse_net_inverser', [
                    'salaire_net' => $salaireNet,
                    'nombre_part' => $nbPart,
                    'transport_legal' => $transportLegal,
                    'categorie_salaire' => $categorySalary,
                    'salaire_catégoriel' => $amountCategoriel,
                    'prime_amount' => $amountPrime,
                    'assurance' => $assurance,
                    'impot_brut' => $impotBrut,
                    'credit_impot' => $creditImpot,
                    'impot_net' => $impotNet,
                    'cnps' => $amountCnps,
                    'amount_brut' => $salaireBrut,
                    'sursalaire' => $sursalaire
                ]);
            }
        }

        return $this->render('dev_paie/salaire_inverse/inverse_net.html.twig', [
            'categorie_salaires' => $this->categoryRepository->findCategorie(),
            'primes' => $primes,
            'selectionsPrime' => $selectionsPrime,
        ]);
    }

    public function amountImpotBrutInverse(int $netSalary): float|int
    {

        $netImposable = $netSalary;
        $tranchesImposition = [
            ['min' => 0, 'limite' => 75000, 'taux' => 0],
            ['min' => 75001, 'limite' => 240000, 'taux' => 0.16],
            ['min' => 240001, 'limite' => 800000, 'taux' => 0.21],
            ['min' => 800001, 'limite' => 2400000, 'taux' => 0.24],
            ['min' => 2400001, 'limite' => 8000000, 'taux' => 0.28],
            ['min' => 8000001, 'limite' => PHP_INT_MAX, 'taux' => 0.32],
        ];

        $impotBrut = 0;

        foreach ($tranchesImposition as $tranche) {
            $limiteMin = $tranche['min'];
            $limiteMax = $tranche['limite'];
            $taux = $tranche['taux'];
            if ($netImposable > $limiteMin && $netImposable >= $limiteMax) {
                $montantImposable = ($limiteMax - $limiteMin) * $taux;
                $impotBrut += $montantImposable;
            } else if ($netImposable > $limiteMin) {
                $montantImposable = ($netImposable - $limiteMin) * $taux;
                $impotBrut += $montantImposable;
                break;
            }
        }

        return $impotBrut;
    }

    public function amountCreditImpotInverse(float $nbPart): float|int
    {
        $nbrePart = $nbPart;
        $creditImpot = null;
        switch ($nbrePart) {
            case 1;
                $creditImpot = 0;
                break;
            case 1.5;
                $creditImpot = 5500;
                break;
            case 2;
                $creditImpot = 11000;
                break;
            case 2.5;
                $creditImpot = 16500;
                break;
            case 3;
                $creditImpot = 22000;
                break;
            case 3.5;
                $creditImpot = 27500;
                break;
            case 4;
                $creditImpot = 33000;
                break;
            case 4.5;
                $creditImpot = 38500;
                break;
            case 5;
                $creditImpot = 44000;
                break;
        }
        return $creditImpot;
    }

    public function amountCNPSInverse(float $netSalary): float|int
    {
        $netImposable = $netSalary;
        if ($netImposable > 1647314) {
            $netImposable = 1647314;
        }
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS']);
        return ceil($netImposable * $categoryRate->getValue() / 100);
    }
}