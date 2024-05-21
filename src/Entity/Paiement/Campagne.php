<?php

namespace App\Entity\Paiement;

use App\Entity\DevPaie\Operation;
use App\Entity\DossierPersonal\Personal;
use App\Repository\Paiement\CampagneRepository;
use App\Utils\Horodatage;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampagneRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Campagne
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $closedAt = null;

    #[ORM\Column]
    private ?bool $active = null;
    #[ORM\Column]
    private ?bool $ordinary = null;

    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $lastCampagne = null;

    #[ORM\ManyToMany(targetEntity: Personal::class, inversedBy: 'campagnes')]
    private Collection $personal;

    #[ORM\OneToMany(mappedBy: 'campagne', targetEntity: Payroll::class)]
    private Collection $payrolls;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\OneToMany(mappedBy: 'campagne', targetEntity: Operation::class)]
    private Collection $operations;

    public function __construct()
    {
        $this->personal = new ArrayCollection();
        $this->payrolls = new ArrayCollection();
        $this->operations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartedAt(): ?DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getClosedAt(): ?DateTimeInterface
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTimeInterface $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
    public function isOrdinary(): ?bool
    {
        return $this->ordinary;
    }

    public function setOrdinary(bool $ordinary): static
    {
        $this->ordinary = $ordinary;

        return $this;
    }

    public function getLastCampagne(): ?self
    {
        return $this->lastCampagne;
    }

    public function setLastCampagne(?self $lastCampagne): static
    {
        $this->lastCampagne = $lastCampagne;

        return $this;
    }

    /**
     * @return Collection<int, Personal>
     */
    public function getPersonal(): Collection
    {
        return $this->personal;
    }

    public function addPersonal(Personal $personal): static
    {
        if (!$this->personal->contains($personal)) {
            $this->personal->add($personal);
        }

        return $this;
    }

    public function removePersonal(Personal $personal): static
    {
        $this->personal->removeElement($personal);

        return $this;
    }

    /**
     * @return Collection<int, Payroll>
     */
    public function getPayrolls(): Collection
    {
        return $this->payrolls;
    }

    public function addPayroll(Payroll $payroll): static
    {
        if (!$this->payrolls->contains($payroll)) {
            $this->payrolls->add($payroll);
            $payroll->setCampagne($this);
        }

        return $this;
    }

    public function removePayroll(Payroll $payroll): static
    {
        if ($this->payrolls->removeElement($payroll)) {
            // set the owning side to null (unless already changed)
            if ($payroll->getCampagne() === $this) {
                $payroll->setCampagne(null);
            }
        }

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

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setCampagne($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getCampagne() === $this) {
                $operation->setCampagne(null);
            }
        }

        return $this;
    }

}