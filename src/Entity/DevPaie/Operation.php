<?php

namespace App\Entity\DevPaie;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Entity\User;
use App\Repository\DevPaie\OperationRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Operation
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeOperations = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountBrut = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountNet = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateOperation = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Campagne $campagne = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3, nullable: true)]
    private ?string $amountMensualite = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1
    )]
    private ?int $nbMensualite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3, nullable: true)]
    private ?string $remaining = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statusPay = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3, nullable: true)]
    private ?string $amountRefund = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeOperations(): ?string
    {
        return $this->typeOperations;
    }

    public function setTypeOperations(?string $typeOperations): static
    {
        $this->typeOperations = $typeOperations;

        return $this;
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

    public function getAmountBrut(): ?string
    {
        return $this->amountBrut;
    }

    public function setAmountBrut(?string $amountBrut): static
    {
        $this->amountBrut = $amountBrut;

        return $this;
    }

    public function getAmountNet(): ?string
    {
        return $this->amountNet;
    }

    public function setAmountNet(?string $amountNet): static
    {
        $this->amountNet = $amountNet;

        return $this;
    }

    public function getDateOperation(): ?\DateTimeInterface
    {
        return $this->dateOperation;
    }

    public function setDateOperation(?\DateTimeInterface $dateOperation): static
    {
        $this->dateOperation = $dateOperation;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmountMensualite(): ?string
    {
        return $this->amountMensualite;
    }

    public function setAmountMensualite(?string $amountMensualite): static
    {
        $this->amountMensualite = $amountMensualite;

        return $this;
    }

    public function getNbMensualite(): ?int
    {
        return $this->nbMensualite;
    }

    public function setNbMensualite(?int $nbMensualite): static
    {
        $this->nbMensualite = $nbMensualite;

        return $this;
    }

    public function getRemaining(): ?string
    {
        return $this->remaining;
    }

    public function setRemaining(?string $remaining): static
    {
        $this->remaining = $remaining;

        return $this;
    }

    public function getStatusPay(): ?string
    {
        return $this->statusPay;
    }

    public function setStatusPay(?string $statusPay): static
    {
        $this->statusPay = $statusPay;

        return $this;
    }

    public function getAmountRefund(): ?string
    {
        return $this->amountRefund;
    }

    public function setAmountRefund(?string $amountRefund): static
    {
        $this->amountRefund = $amountRefund;

        return $this;
    }
}
