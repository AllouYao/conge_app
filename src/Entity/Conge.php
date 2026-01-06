<?php

namespace App\Entity;

use App\Entity\User;
use DateTimeInterface;
use App\Utils\Horodatage;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\CongeRepository;

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
   
    #[ORM\Column]
    private ?bool $isConge = null;

    #[ORM\ManyToOne(inversedBy: 'conges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $totalDays = null;

    #[ORM\ManyToOne(inversedBy: 'conges')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'conges')]
    private ?TypeConge $typeConge = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

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

    public function getTotalDays(): ?string
    {
        return $this->totalDays;
    }

    public function setTotalDays(?string $totalDays): static
    {
        $this->totalDays = $totalDays;

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

    public function getTypeConge(): ?TypeConge
    {
        return $this->typeConge;
    }

    public function setTypeConge(?TypeConge $typeConge): static
    {
        $this->typeConge = $typeConge;

        return $this;
    }

}
