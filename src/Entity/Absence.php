<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\AbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Utils\Horodatage;


#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Absence
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]

    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endedDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $justified = null;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    private ?Personal $personal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartedDate(): ?\DateTimeInterface
    {
        return $this->startedDate;
    }

    public function setStartedDate(\DateTimeInterface $startedDate): static
    {
        $this->startedDate = $startedDate;

        return $this;
    }

    public function getEndedDate(): ?\DateTimeInterface
    {
        return $this->endedDate;
    }

    public function setEndedDate(\DateTimeInterface $endedDate): static
    {
        $this->endedDate = $endedDate;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $comment): static
    {
        $this->description = $comment;

        return $this;
    }

    public function isJustified(): ?bool
    {
        return $this->justified;
    }

    public function setJustified(bool $justified): static
    {
        $this->justified = $justified;

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
    public function getTotalDay()
    {

        $diff = $this->endedDate->diff($this->startedDate);
        $totalJour = $diff->format('%d');

        return $totalJour;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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


   
}