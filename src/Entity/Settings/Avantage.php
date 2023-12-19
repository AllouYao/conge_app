<?php

namespace App\Entity\Settings;

use App\Entity\DossierPersonal\Salary;
use App\Repository\Settings\AvantageRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvantageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Avantage
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numPiece = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amountLogement = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amountMobilier = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amountElectricite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amountEaux = null;

    #[ORM\OneToMany(mappedBy: 'avantage', targetEntity: Salary::class)]
    private Collection $salaries;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $totalAvantage = null;

    public function __construct()
    {
        $this->salaries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumPiece(): ?int
    {
        return $this->numPiece;
    }

    public function setNumPiece(int $numPiece): static
    {
        $this->numPiece = $numPiece;

        return $this;
    }

    public function getAmountLogement(): ?string
    {
        return $this->amountLogement;
    }

    public function setAmountLogement(string $amountLogement): static
    {
        $this->amountLogement = $amountLogement;

        return $this;
    }

    public function getAmountMobilier(): ?string
    {
        return $this->amountMobilier;
    }

    public function setAmountMobilier(string $amountMobilier): static
    {
        $this->amountMobilier = $amountMobilier;

        return $this;
    }

    public function getAmountElectricite(): ?string
    {
        return $this->amountElectricite;
    }

    public function setAmountElectricite(string $amountElectricite): static
    {
        $this->amountElectricite = $amountElectricite;

        return $this;
    }

    public function getAmountEaux(): ?string
    {
        return $this->amountEaux;
    }

    public function setAmountEaux(string $amountEaux): static
    {
        $this->amountEaux = $amountEaux;

        return $this;
    }

    /**
     * @return Collection<int, Salary>
     */
    public function getSalaries(): Collection
    {
        return $this->salaries;
    }

    public function addSalary(Salary $salary): static
    {
        if (!$this->salaries->contains($salary)) {
            $this->salaries->add($salary);
            $salary->setAvantage($this);
        }

        return $this;
    }

    public function removeSalary(Salary $salary): static
    {
        if ($this->salaries->removeElement($salary)) {
            // set the owning side to null (unless already changed)
            if ($salary->getAvantage() === $this) {
                $salary->setAvantage(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
        return $this->numPiece . '-' . number_format($this->getTotalAvantage(), 0, ',', ' ');
    }

    public function getTotalAvantage(): ?string
    {
        return $this->totalAvantage;
    }

    public function setTotalAvantage(string $totalAvantage): static
    {
        $this->totalAvantage = $totalAvantage;

        return $this;
    }
}
