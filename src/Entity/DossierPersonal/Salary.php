<?php

namespace App\Entity\DossierPersonal;

use App\Entity\Settings\Avantage;
use App\Repository\DossierPersonal\SalaryRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalaryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Salary
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $baseAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $sursalaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $brutAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $primeTransport = null;

    #[ORM\OneToOne(inversedBy: 'salary', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $brutImposable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $smig = null;


    #[ORM\OneToMany(mappedBy: 'salary', targetEntity: DetailSalary::class, orphanRemoval: true)]
    private Collection $detailSalaries;

    #[ORM\ManyToOne(inversedBy: 'salaries')]
    private ?Avantage $avantage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $totalPrimeJuridique = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $primeAciennete = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $transportImposable = null;

    #[ORM\OneToMany(mappedBy: 'salary', targetEntity: DetailPrimeSalary::class)]
    private Collection $detailPrimeSalaries;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountAventage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $totalAutrePrimes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $gratification = null;

    #[ORM\OneToMany(mappedBy: 'salary', targetEntity: DetailRetenueForfetaire::class)]
    private Collection $detailRetenueForfetaires;

    public function __construct()
    {
        $this->detailSalaries = new ArrayCollection();
        $this->detailPrimeSalaries = new ArrayCollection();
        $this->detailRetenueForfetaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseAmount(): ?string
    {
        return $this->baseAmount;
    }

    public function setBaseAmount(string $baseAmount): static
    {
        $this->baseAmount = $baseAmount;

        return $this;
    }

    public function getSursalaire(): ?string
    {
        return $this->sursalaire;
    }

    public function setSursalaire(?string $sursalaire): static
    {
        $this->sursalaire = $sursalaire;

        return $this;
    }

    public function getBrutAmount(): ?string
    {
        return $this->brutAmount;
    }

    public function setBrutAmount(string $brutAmount): static
    {
        $this->brutAmount = $brutAmount;

        return $this;
    }

    public function getPrimeTransport(): ?string
    {
        return $this->primeTransport;
    }

    public function setPrimeTransport(?string $primeTransport): static
    {
        $this->primeTransport = $primeTransport;

        return $this;
    }

    public function getPersonal(): ?Personal
    {
        return $this->personal;
    }

    public function setPersonal(Personal $personal): static
    {
        $this->personal = $personal; 

        return $this;
    }

    public function getBrutImposable(): ?string
    {
        return $this->brutImposable;
    }

    public function setBrutImposable(string $brutImposable): static
    {
        $this->brutImposable = $brutImposable;

        return $this;
    }

    public function getSmig(): ?string
    {
        return $this->smig;
    }

    public function setSmig(string $smig): static
    {
        $this->smig = $smig;

        return $this;
    }

    /**
     * @return Collection<int, DetailSalary>
     */
    public function getDetailSalaries(): Collection
    {
        return $this->detailSalaries;
    }

    public function addDetailSalary(DetailSalary $detailSalary): static
    {
        if (!$this->detailSalaries->contains($detailSalary)) {
            $this->detailSalaries->add($detailSalary);
            $detailSalary->setSalary($this);
        }

        return $this;
    }

    public function removeDetailSalary(DetailSalary $detailSalary): static
    {
        if ($this->detailSalaries->removeElement($detailSalary)) {
            // set the owning side to null (unless already changed)
            if ($detailSalary->getSalary() === $this) {
                $detailSalary->setSalary(null);
            }
        }

        return $this;
    }

    public function getAvantage(): ?Avantage
    {
        return $this->avantage;
    }

    public function setAvantage(?Avantage $avantage): static
    {
        $this->avantage = $avantage;
        return $this;
    }

    public function getTotalPrimeJuridique(): ?string
    {
        return $this->totalPrimeJuridique;
    }

    public function setTotalPrimeJuridique(?string $totalPrimeJuridique): static
    {
        $this->totalPrimeJuridique = $totalPrimeJuridique;

        return $this;
    }

    public function getPrimeAciennete(): ?string
    {
        return $this->primeAciennete;
    }

    public function setPrimeAciennete(?string $primeAciennete): static
    {
        $this->primeAciennete = $primeAciennete;

        return $this;
    }

    public function getTransportImposable(): ?string
    {
        return $this->transportImposable;
    }

    public function setTransportImposable(?string $transportImposable): static
    {
        $this->transportImposable = $transportImposable;

        return $this;
    }

    /**
     * @return Collection<int, DetailPrimeSalary>
     */
    public function getDetailPrimeSalaries(): Collection
    {
        return $this->detailPrimeSalaries;
    }

    public function addDetailPrimeSalary(DetailPrimeSalary $detailPrimeSalary): static
    {
        if (!$this->detailPrimeSalaries->contains($detailPrimeSalary)) {
            $this->detailPrimeSalaries->add($detailPrimeSalary);
            $detailPrimeSalary->setSalary($this);
        }

        return $this;
    }

    public function removeDetailPrimeSalary(DetailPrimeSalary $detailPrimeSalary): static
    {
        if ($this->detailPrimeSalaries->removeElement($detailPrimeSalary)) {
            // set the owning side to null (unless already changed)
            if ($detailPrimeSalary->getSalary() === $this) {
                $detailPrimeSalary->setSalary(null);
            }
        }

        return $this;
    }

    public function getAmountAventage(): ?string
    {
        return $this->amountAventage;
    }

    public function setAmountAventage(?string $amountAventage): static
    {
        $this->amountAventage = $amountAventage;

        return $this;
    }

    public function getTotalAutrePrimes(): ?string
    {
        return $this->totalAutrePrimes;
    }

    public function setTotalAutrePrimes(?string $totalAutrePrimes): static
    {
        $this->totalAutrePrimes = $totalAutrePrimes;

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

    /**
     * @return Collection<int, DetailRetenueForfetaire>
     */
    public function getDetailRetenueForfetaires(): Collection
    {
        return $this->detailRetenueForfetaires;
    }

    public function addDetailRetenueForfetaire(DetailRetenueForfetaire $detailRetenueForfetaire): static
    {
        if (!$this->detailRetenueForfetaires->contains($detailRetenueForfetaire)) {
            $this->detailRetenueForfetaires->add($detailRetenueForfetaire);
            $detailRetenueForfetaire->setSalary($this);
        }

        return $this;
    }

    public function removeDetailRetenueForfetaire(DetailRetenueForfetaire $detailRetenueForfetaire): static
    {
        if ($this->detailRetenueForfetaires->removeElement($detailRetenueForfetaire)) {
            // set the owning side to null (unless already changed)
            if ($detailRetenueForfetaire->getSalary() === $this) {
                $detailRetenueForfetaire->setSalary(null);
            }
        }

        return $this;
    }
}
