<?php

namespace App\Entity\Impots;

use App\Repository\Impots\CategoryChargeRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryChargeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CategoryCharge
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $codification = null;

    #[ORM\Column(length: 255)]
    private ?string $intitule = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(length: 255)]
    private ?string $typeCharge = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodification(): ?string
    {
        return $this->codification;
    }

    public function setCodification(string $codification): static
    {
        $this->codification = $codification;

        return $this;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getTypeCharge(): ?string
    {
        return $this->typeCharge;
    }

    public function setTypeCharge(string $typeCharge): static
    {
        $this->typeCharge = $typeCharge;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

}
