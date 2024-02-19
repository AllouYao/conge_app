<?php

namespace App\Entity\DossierPersonal;

use App\Repository\DossierPersonal\ContractRepository;
use App\Utils\Horodatage;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contract
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateEmbauche = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateEffet = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tempsContractuel = null;

    #[ORM\Column(length: 255)]
    private ?string $typeContrat = null;

    #[ORM\OneToOne(inversedBy: 'contract', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(length: 255)]
    private ?string $refContract = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEmbauche(): ?DateTimeInterface
    {
        return $this->dateEmbauche;
    }

    public function setDateEmbauche(?DateTimeInterface $dateEmbauche): static
    {
        $this->dateEmbauche = $dateEmbauche;

        return $this;
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

    public function getDateFin(): ?DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getTempsContractuel(): ?string
    {
        return $this->tempsContractuel;
    }

    public function setTempsContractuel(?string $tempsContractuel): static
    {
        $this->tempsContractuel = $tempsContractuel;

        return $this;
    }

    public function getTypeContrat(): ?string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(string $typeContrat): static
    {
        $this->typeContrat = $typeContrat;

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

    public function getRefContract(): ?string
    {
        return $this->refContract;
    }

    public function setRefContract(string $refContract): static
    {
        $this->refContract = $refContract;

        return $this;
    }
}
