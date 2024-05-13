<?php

namespace App\Entity\DevPaie;

use App\Entity\DossierPersonal\Conge;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DevPaie\CongePartielRepository;

#[ORM\Entity(repositoryClass: CongePartielRepository::class)]
class CongePartiel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateDepart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateRetour = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $allocationConge = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $days = null;

    #[ORM\ManyToOne(inversedBy: 'congePartiels')]
    private ?Conge $conge = null;

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
    
    public function getAllocationConge(): ?string
    {
        return $this->allocationConge;
    }

    public function setAllocationConge(?string $allocationConge): static
    {
        $this->allocationConge = $allocationConge;

        return $this;
    }
    public function getDays(): ?string
    {
        return $this->days;
    }

    public function setDays(?string $days): static
    {
        $this->days = $days;

        return $this;
    }
    public function setConge(?Conge $conge): static
    {
        $this->conge = $conge;

        return $this;
    }

    public function getConge(): ?Conge
    {
        return $this->conge;
    }

    
}
