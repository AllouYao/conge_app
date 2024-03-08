<?php

namespace App\Entity\DevPaie;

use App\Entity\DossierPersonal\Personal;
use App\Entity\User;
use App\Repository\DevPaie\OperationRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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
}
