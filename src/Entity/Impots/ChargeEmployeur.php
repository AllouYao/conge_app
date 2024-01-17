<?php

namespace App\Entity\Impots;

use App\Entity\DossierPersonal\Personal;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChargeEmployeurRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ChargeEmployeur
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'chargeEmployeurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountIS = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountCR = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountPF = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountAT = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $totalChargeEmployeur = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $totalRetenuCNPS = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountCMU = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountTA = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountFPC = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountAnnuelFPC = null;

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

    public function getAmountIS(): ?string
    {
        return $this->amountIS;
    }

    public function setAmountIS(?string $amountIS): static
    {
        $this->amountIS = $amountIS;

        return $this;
    }

    public function getAmountCR(): ?string
    {
        return $this->amountCR;
    }

    public function setAmountCR(?string $amountCR): static
    {
        $this->amountCR = $amountCR;

        return $this;
    }

    public function getAmountPF(): ?string
    {
        return $this->amountPF;
    }

    public function setAmountPF(?string $amountPF): static
    {
        $this->amountPF = $amountPF;

        return $this;
    }

    public function getAmountAT(): ?string
    {
        return $this->amountAT;
    }

    public function setAmountAT(?string $amountAT): static
    {
        $this->amountAT = $amountAT;

        return $this;
    }

    public function getTotalChargeEmployeur(): ?string
    {
        return $this->totalChargeEmployeur;
    }

    public function setTotalChargeEmployeur(?string $totalChargeEmployeur): static
    {
        $this->totalChargeEmployeur = $totalChargeEmployeur;

        return $this;
    }

    public function getTotalRetenuCNPS(): ?string
    {
        return $this->totalRetenuCNPS;
    }

    public function setTotalRetenuCNPS(?string $totalRetenuCNPS): static
    {
        $this->totalRetenuCNPS = $totalRetenuCNPS;

        return $this;
    }

    public function getAmountCMU(): ?string
    {
        return $this->amountCMU;
    }

    public function setAmountCMU(?string $amountCMU): static
    {
        $this->amountCMU = $amountCMU;

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

    public function getAmountFPC(): ?string
    {
        return $this->amountFPC;
    }

    public function setAmountFPC(?string $amountFPC): static
    {
        $this->amountFPC = $amountFPC;

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
}
