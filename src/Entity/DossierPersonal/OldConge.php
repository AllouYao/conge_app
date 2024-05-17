<?php

namespace App\Entity\DossierPersonal;

use App\Repository\DossierPersonal\OldCongeRepository;
use Doctrine\DBAL\Types\Types;
use App\Utils\Horodatage;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OldCongeRepository::class)]
#[ORM\HasLifecycleCallbacks]

class OldConge
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRetour = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaryAverage = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\OneToOne(inversedBy: 'oldConge', cascade: ['persist', 'remove'])]
    private ?Personal $personal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateRetour(): ?\DateTimeInterface
    {
        return $this->dateRetour;
    }

    public function setDateRetour(?\DateTimeInterface $dateRetour): static
    {
        $this->dateRetour = $dateRetour;

        return $this;
    }

    public function getSalaryAverage(): ?string
    {
        return $this->salaryAverage;
    }

    public function setSalaryAverage(?string $salaryAverage): static
    {
        $this->salaryAverage = $salaryAverage;

        return $this;
    }
    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

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
