<?php

namespace App\Entity\DossierPersonal;

use App\Repository\DossierPersonal\CongeRepository;
use App\Utils\Horodatage;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CongeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Conge
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateDepart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateRetour = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateDernierRetour = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaireMoyen = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $allocationConge = null;

    #[ORM\Column]
    private ?bool $isConge = null;

    #[ORM\ManyToOne(inversedBy: 'conges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeConge = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $gratification = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDepart(): ?DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(DateTimeInterface $dateDepart): static
    {
        $this->dateDepart = $dateDepart;

        return $this;
    }

    public function getDateRetour(): ?DateTimeInterface
    {
        return $this->dateRetour;
    }

    public function setDateRetour(DateTimeInterface $dateRetour): static
    {
        $this->dateRetour = $dateRetour;

        return $this;
    }

    public function getDateDernierRetour(): ?DateTimeInterface
    {
        return $this->dateDernierRetour;
    }

    public function setDateDernierRetour(?DateTimeInterface $dateDernierRetour): static
    {
        $this->dateDernierRetour = $dateDernierRetour;

        return $this;
    }

    public function getSalaireMoyen(): ?string
    {
        return $this->salaireMoyen;
    }

    public function setSalaireMoyen(?string $salaireMoyen): static
    {
        $this->salaireMoyen = $salaireMoyen;

        return $this;
    }

    public function getAllocationConge(): ?string
    {
        return $this->allocationConge;
    }

    public function setAllocationConge(?string $allocationConge): static
    {
        $this->allocationConge = $allocationConge;

        return $this;
    }

    public function isIsConge(): ?bool
    {
        return $this->isConge;
    }

    public function setIsConge(bool $isConge): static
    {
        $this->isConge = $isConge;

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

    public function getTypeConge(): ?string
    {
        return $this->typeConge;
    }

    public function setTypeConge(?string $typeConge): static
    {
        $this->typeConge = $typeConge;

        return $this;
    }


    public function getGratification(): ?string
    {
        return $this->gratification;
    }

    public function setGratification(?string $gratification): static
    {
        $this->gratification = $gratification;

        return $this;
    }
}
