<?php

namespace App\Entity\Impots;

use App\Entity\DossierPersonal\Personal;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChargePersonalsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ChargePersonals
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'chargePersonals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountIts = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountCNPS = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountCMU = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $AmountTotalChargePersonal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $numPart = null;

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

    public function getAmountIts(): ?string
    {
        return $this->amountIts;
    }

    public function setAmountIts(?string $amountIts): static
    {
        $this->amountIts = $amountIts;

        return $this;
    }

    public function getAmountCNPS(): ?string
    {
        return $this->amountCNPS;
    }

    public function setAmountCNPS(?string $amountCNPS): static
    {
        $this->amountCNPS = $amountCNPS;

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

    public function getAmountTotalChargePersonal(): ?string
    {
        return $this->AmountTotalChargePersonal;
    }

    public function setAmountTotalChargePersonal(?string $AmountTotalChargePersonal): static
    {
        $this->AmountTotalChargePersonal = $AmountTotalChargePersonal;

        return $this;
    }

    public function getNumPart(): ?string
    {
        return $this->numPart;
    }

    public function setNumPart(string $numPart): static
    {
        $this->numPart = $numPart;

        return $this;
    }
}
