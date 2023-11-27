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
}
