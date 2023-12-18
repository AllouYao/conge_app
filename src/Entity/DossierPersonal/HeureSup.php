<?php

namespace App\Entity\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeureSupRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HeureSup
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedHour = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endedHour = null;

    #[ORM\ManyToOne(inversedBy: 'heureSups')]
    private ?Personal $personal = null;

    #[ORM\Column(length: 255)]
    private ?string $typeDay = null;

    #[ORM\Column(length: 255)]
    private ?string $typeJourOrNuit = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endedDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartedHour(): ?\DateTimeInterface
    {
        return $this->startedHour;
    }

    public function setStartedHour(\DateTimeInterface $startedHour): static
    {
        $this->startedHour = $startedHour;

        return $this;
    }

    public function getEndedHour(): ?\DateTimeInterface
    {
        return $this->endedHour;
    }

    public function setEndedHour(\DateTimeInterface $endedHour): static
    {
        $this->endedHour = $endedHour;

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

    public function getTypeDay(): ?string
    {
        return $this->typeDay;
    }

    public function setTypeDay(string $typeDay): static
    {
        $this->typeDay = $typeDay;

        return $this;
    }

    public function getTypeJourOrNuit(): ?string
    {
        return $this->typeJourOrNuit;
    }

    public function setTypeJourOrNuit(string $typeJourOrNuit): static
    {
        $this->typeJourOrNuit = $typeJourOrNuit;

        return $this;
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

    public function getTotalHorraire()
    {

        $diff = $this->startedHour->diff($this->endedHour);
        $totalHorraire = $diff->format('%h');

        return $totalHorraire;
    }
}