<?php

namespace App\Entity\Settings;

use App\Entity\DossierPersonal\DetailPrimeSalary;
use App\Entity\DossierPersonal\DetailSalary;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrimesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Primes
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $intitule = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $taux = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;


    #[ORM\OneToMany(mappedBy: 'prime', targetEntity: DetailSalary::class)]
    private Collection $detailSalaries;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'prime', targetEntity: DetailPrimeSalary::class, orphanRemoval: true)]
    private Collection $detailPrimeSalaries;

    public function __construct()
    {
        $this->detailSalaries = new ArrayCollection();
        $this->detailPrimeSalaries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function __toString(): string
    {
        return $this->intitule;
    }

    public function getTaux(): ?string
    {
        return $this->taux;
    }

    public function setTaux(string $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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
            $detailSalary->setPrime($this);
        }

        return $this;
    }

    public function removeDetailSalary(DetailSalary $detailSalary): static
    {
        if ($this->detailSalaries->removeElement($detailSalary)) {
            // set the owning side to null (unless already changed)
            if ($detailSalary->getPrime() === $this) {
                $detailSalary->setPrime(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
            $detailPrimeSalary->setPrime($this);
        }

        return $this;
    }

    public function removeDetailPrimeSalary(DetailPrimeSalary $detailPrimeSalary): static
    {
        if ($this->detailPrimeSalaries->removeElement($detailPrimeSalary)) {
            // set the owning side to null (unless already changed)
            if ($detailPrimeSalary->getPrime() === $this) {
                $detailPrimeSalary->setPrime(null);
            }
        }

        return $this;
    }
}
