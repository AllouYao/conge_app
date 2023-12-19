<?php

namespace App\Entity\DossierPersonal;

use App\Repository\DossierPersonal\AffectationRepository;
use App\Utils\Horodatage;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Affectation
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateEffet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $groupeTravail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $poste = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tauxAffectation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\OneToOne(inversedBy: 'affectation', cascade: ['persist', 'remove'])]
    private ?Personal $personal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEffet(): ?DateTimeInterface
    {
        return $this->dateEffet;
    }

    public function setDateEffet(?DateTimeInterface $dateEffet): static
    {
        $this->dateEffet = $dateEffet;

        return $this;
    }

    public function getGroupeTravail(): ?string
    {
        return $this->groupeTravail;
    }

    public function setGroupeTravail(?string $groupeTravail): static
    {
        $this->groupeTravail = $groupeTravail;

        return $this;
    }

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(?string $poste): static
    {
        $this->poste = $poste;

        return $this;
    }

    public function getTauxAffectation(): ?string
    {
        return $this->tauxAffectation;
    }

    public function setTauxAffectation(?string $tauxAffectation): static
    {
        $this->tauxAffectation = $tauxAffectation;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

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
}
