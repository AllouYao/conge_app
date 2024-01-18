<?php

namespace App\Entity\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Repository\Paiement\PayrollRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PayrollRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Payroll
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'payrolls')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $numberPart = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $baseAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $sursalaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $brutAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $imposableAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaryIts = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaryCnps = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaryCmu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $fixcalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaryTransport = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $netPayer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salarySante = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $employeurIs = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $employeurFdfp = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $employeurCmu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $employeurPf = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $employeurAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $employeurCnps = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $employeurSante = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $employeurCr = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $fixcalAmountEmployeur = null;

    #[ORM\ManyToOne(inversedBy: 'payrolls')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campagne $campagne = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $masseSalary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $service = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $departement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categories = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEmbauche = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numCnps = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $majorationAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $AncienneteAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $congesPayesAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $primeFonctionAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $primeLogementAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $indemniteFonctionAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $indemniteLogementAmount = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountTA = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountAnnuelFPC = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountFPC = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountTransImposable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountAvantageImposable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $aventageNonImposable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountPrimePanier = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountPrimeSalissure = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountPrimeOutillage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountPrimeTenueTrav = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountPrimeRendement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonal(): ?Personal
    {
        return $this->personal;
    }

    public function setPersonal(?Personal $personal): static
    {
        $this->personal = $personal;

        return $this;
    }

    public function getNumberPart(): ?string
    {
        return $this->numberPart;
    }

    public function setNumberPart(?string $numberPart): static
    {
        $this->numberPart = $numberPart;

        return $this;
    }

    public function getBaseAmount(): ?string
    {
        return $this->baseAmount;
    }

    public function setBaseAmount(?string $baseAmount): static
    {
        $this->baseAmount = $baseAmount;

        return $this;
    }

    public function getSursalaire(): ?string
    {
        return $this->sursalaire;
    }

    public function setSursalaire(?string $sursalaire): static
    {
        $this->sursalaire = $sursalaire;

        return $this;
    }

    public function getBrutAmount(): ?string
    {
        return $this->brutAmount;
    }

    public function setBrutAmount(?string $brutAmount): static
    {
        $this->brutAmount = $brutAmount;

        return $this;
    }

    public function getImposableAmount(): ?string
    {
        return $this->imposableAmount;
    }

    public function setImposableAmount(?string $imposableAmount): static
    {
        $this->imposableAmount = $imposableAmount;

        return $this;
    }

    public function getSalaryIts(): ?string
    {
        return $this->salaryIts;
    }

    public function setSalaryIts(?string $salaryIts): static
    {
        $this->salaryIts = $salaryIts;

        return $this;
    }

    public function getSalaryCnps(): ?string
    {
        return $this->salaryCnps;
    }

    public function setSalaryCnps(?string $salaryCnps): static
    {
        $this->salaryCnps = $salaryCnps;

        return $this;
    }

    public function getSalaryCmu(): ?string
    {
        return $this->salaryCmu;
    }

    public function setSalaryCmu(?string $salaryCmu): static
    {
        $this->salaryCmu = $salaryCmu;

        return $this;
    }

    public function getFixcalAmount(): ?string
    {
        return $this->fixcalAmount;
    }

    public function setFixcalAmount(?string $fixcalAmount): static
    {
        $this->fixcalAmount = $fixcalAmount;

        return $this;
    }

    public function getSalaryTransport(): ?string
    {
        return $this->salaryTransport;
    }

    public function setSalaryTransport(?string $salaryTransport): static
    {
        $this->salaryTransport = $salaryTransport;

        return $this;
    }

    public function getNetPayer(): ?string
    {
        return $this->netPayer;
    }

    public function setNetPayer(?string $netPayer): static
    {
        $this->netPayer = $netPayer;

        return $this;
    }

    public function getSalarySante(): ?string
    {
        return $this->salarySante;
    }

    public function setSalarySante(?string $salarySante): static
    {
        $this->salarySante = $salarySante;

        return $this;
    }

    public function getEmployeurIs(): ?string
    {
        return $this->employeurIs;
    }

    public function setEmployeurIs(?string $employeurIs): static
    {
        $this->employeurIs = $employeurIs;

        return $this;
    }

    public function getEmployeurFdfp(): ?string
    {
        return $this->employeurFdfp;
    }

    public function setEmployeurFdfp(?string $employeurFdfp): static
    {
        $this->employeurFdfp = $employeurFdfp;

        return $this;
    }

    public function getEmployeurCmu(): ?string
    {
        return $this->employeurCmu;
    }

    public function setEmployeurCmu(?string $employeurCmu): static
    {
        $this->employeurCmu = $employeurCmu;

        return $this;
    }

    public function getEmployeurPf(): ?string
    {
        return $this->employeurPf;
    }

    public function setEmployeurPf(?string $employeurPf): static
    {
        $this->employeurPf = $employeurPf;

        return $this;
    }

    public function getEmployeurAt(): ?string
    {
        return $this->employeurAt;
    }

    public function setEmployeurAt(?string $employeurAt): static
    {
        $this->employeurAt = $employeurAt;

        return $this;
    }

    public function getEmployeurCnps(): ?string
    {
        return $this->employeurCnps;
    }

    public function setEmployeurCnps(?string $employeurCnps): static
    {
        $this->employeurCnps = $employeurCnps;

        return $this;
    }

    public function getEmployeurSante(): ?string
    {
        return $this->employeurSante;
    }

    public function setEmployeurSante(?string $employeurSante): static
    {
        $this->employeurSante = $employeurSante;

        return $this;
    }

    public function getEmployeurCr(): ?string
    {
        return $this->employeurCr;
    }

    public function setEmployeurCr(?string $employeurCr): static
    {
        $this->employeurCr = $employeurCr;

        return $this;
    }

    public function getFixcalAmountEmployeur(): ?string
    {
        return $this->fixcalAmountEmployeur;
    }

    public function setFixcalAmountEmployeur(?string $fixcalAmountEmployeur): static
    {
        $this->fixcalAmountEmployeur = $fixcalAmountEmployeur;

        return $this;
    }

    public function getCampagne(): ?Campagne
    {
        return $this->campagne;
    }

    public function setCampagne(?Campagne $campagne): static
    {
        $this->campagne = $campagne;

        return $this;
    }

    public function getMasseSalary(): ?string
    {
        return $this->masseSalary;
    }

    public function setMasseSalary(?string $masseSalary): static
    {
        $this->masseSalary = $masseSalary;

        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getDepartement(): ?string
    {
        return $this->departement;
    }

    public function setDepartement(?string $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function getCategories(): ?string
    {
        return $this->categories;
    }

    public function setCategories(?string $categories): static
    {
        $this->categories = $categories;

        return $this;
    }

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->dateEmbauche;
    }

    public function setDateEmbauche(?\DateTimeInterface $dateEmbauche): static
    {
        $this->dateEmbauche = $dateEmbauche;

        return $this;
    }

    public function getNumCnps(): ?string
    {
        return $this->numCnps;
    }

    public function setNumCnps(?string $numCnps): static
    {
        $this->numCnps = $numCnps;

        return $this;
    }

    public function getMajorationAmount(): ?string
    {
        return $this->majorationAmount;
    }

    public function setMajorationAmount(?string $majorationAmount): static
    {
        $this->majorationAmount = $majorationAmount;

        return $this;
    }

    public function getAncienneteAmount(): ?string
    {
        return $this->AncienneteAmount;
    }

    public function setAncienneteAmount(?string $AncienneteAmount): static
    {
        $this->AncienneteAmount = $AncienneteAmount;

        return $this;
    }

    public function getCongesPayesAmount(): ?string
    {
        return $this->congesPayesAmount;
    }

    public function setCongesPayesAmount(?string $congesPayesAmount): static
    {
        $this->congesPayesAmount = $congesPayesAmount;

        return $this;
    }

    public function getPrimeFonctionAmount(): ?string
    {
        return $this->primeFonctionAmount;
    }

    public function setPrimeFonctionAmount(?string $primeFonctionAmount): static
    {
        $this->primeFonctionAmount = $primeFonctionAmount;

        return $this;
    }

    public function getPrimeLogementAmount(): ?string
    {
        return $this->primeLogementAmount;
    }

    public function setPrimeLogementAmount(?string $primeLogementAmount): static
    {
        $this->primeLogementAmount = $primeLogementAmount;

        return $this;
    }

    public function getIndemniteFonctionAmount(): ?string
    {
        return $this->indemniteFonctionAmount;
    }

    public function setIndemniteFonctionAmount(?string $indemniteFonctionAmount): static
    {
        $this->indemniteFonctionAmount = $indemniteFonctionAmount;

        return $this;
    }

    public function getIndemniteLogementAmount(): ?string
    {
        return $this->indemniteLogementAmount;
    }

    public function setIndemniteLogementAmount(?string $indemniteLogementAmount): static
    {
        $this->indemniteLogementAmount = $indemniteLogementAmount;

        return $this;
    }

    public function getAmountTA(): ?string
    {
        return $this->amountTA;
    }

    public function setAmountTA(?string $amountTA): static
    {
        $this->amountTA = $amountTA;

        return $this;
    }

    public function getAmountAnnuelFPC(): ?string
    {
        return $this->amountAnnuelFPC;
    }

    public function setAmountAnnuelFPC(?string $amountAnnuelFPC): static
    {
        $this->amountAnnuelFPC = $amountAnnuelFPC;

        return $this;
    }

    public function getAmountFPC(): ?string
    {
        return $this->amountFPC;
    }

    public function setAmountFPC(?string $amountFPC): static
    {
        $this->amountFPC = $amountFPC;

        return $this;
    }

    public function getAmountTransImposable(): ?string
    {
        return $this->amountTransImposable;
    }

    public function setAmountTransImposable(?string $amountTransImposable): static
    {
        $this->amountTransImposable = $amountTransImposable;

        return $this;
    }

    public function getAmountAvantageImposable(): ?string
    {
        return $this->amountAvantageImposable;
    }

    public function setAmountAvantageImposable(?string $amountAvantageImposable): static
    {
        $this->amountAvantageImposable = $amountAvantageImposable;

        return $this;
    }

    public function getAventageNonImposable(): ?string
    {
        return $this->aventageNonImposable;
    }

    public function setAventageNonImposable(?string $aventageNonImposable): static
    {
        $this->aventageNonImposable = $aventageNonImposable;

        return $this;
    }

    public function getAmountPrimePanier(): ?string
    {
        return $this->amountPrimePanier;
    }

    public function setAmountPrimePanier(?string $amountPrimePanier): static
    {
        $this->amountPrimePanier = $amountPrimePanier;

        return $this;
    }

    public function getAmountPrimeSalissure(): ?string
    {
        return $this->amountPrimeSalissure;
    }

    public function setAmountPrimeSalissure(?string $amountPrimeSalissure): static
    {
        $this->amountPrimeSalissure = $amountPrimeSalissure;

        return $this;
    }

    public function getAmountPrimeOutillage(): ?string
    {
        return $this->amountPrimeOutillage;
    }

    public function setAmountPrimeOutillage(?string $amountPrimeOutillage): static
    {
        $this->amountPrimeOutillage = $amountPrimeOutillage;

        return $this;
    }

    public function getAmountPrimeTenueTrav(): ?string
    {
        return $this->amountPrimeTenueTrav;
    }

    public function setAmountPrimeTenueTrav(?string $amountPrimeTenueTrav): static
    {
        $this->amountPrimeTenueTrav = $amountPrimeTenueTrav;

        return $this;
    }

    public function getAmountPrimeRendement(): ?string
    {
        return $this->amountPrimeRendement;
    }

    public function setAmountPrimeRendement(?string $amountPrimeRendement): static
    {
        $this->amountPrimeRendement = $amountPrimeRendement;

        return $this;
    }
}
