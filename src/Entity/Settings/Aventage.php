<?php

namespace App\Entity\Settings;

use App\Repository\Settings\AventageRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AventageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Aventage
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numbPiece = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amountAventage = null;

    #[ORM\ManyToOne(inversedBy: 'aventages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeAventage $typeAventage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumbPiece(): ?int
    {
        return $this->numbPiece;
    }

    public function setNumbPiece(int $numbPiece): static
    {
        $this->numbPiece = $numbPiece;

        return $this;
    }

    public function getAmountAventage(): ?string
    {
        return $this->amountAventage;
    }

    public function setAmountAventage(string $amountAventage): static
    {
        $this->amountAventage = $amountAventage;

        return $this;
    }

    public function getTypeAventage(): ?TypeAventage
    {
        return $this->typeAventage;
    }

    public function setTypeAventage(?TypeAventage $typeAventage): static
    {
        $this->typeAventage = $typeAventage;

        return $this;
    }
}
