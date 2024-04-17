<?php

namespace App\Entity\DossierPersonal;

use App\Entity\DevPaie\CongePartiel;
use App\Entity\User;
use App\Repository\DossierPersonal\CongeRepository;
use App\Utils\Horodatage;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateReprise = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateDernierRetour = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaireMoyen = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $allocationConge = null;

    #[ORM\Column]
    private ?bool $isConge = null;

    #[ORM\ManyToOne(inversedBy: 'conges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeConge = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typePayementConge = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $gratification = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $days = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $daysPlus = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaryDue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $workMonths = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $totalDays = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $olderDays = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $remainingVacation = null;

    #[ORM\ManyToOne(inversedBy: 'conges')]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'conge', targetEntity: CongePartiel::class)]
    private Collection $congePartiels;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    public function __construct()
    {
        $this->congePartiels = new ArrayCollection();
    }


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
    public function getDateReprise(): ?DateTimeInterface
    {
        return $this->dateReprise;
    }

    public function setDateReprise(DateTimeInterface $dateReprise): static
    {
        $this->dateReprise = $dateReprise;

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

    public function getDateDernierRetour(): ?DateTimeInterface
    {
        return $this->dateDernierRetour;
    }

    public function setDateDernierRetour(?DateTimeInterface $dateDernierRetour): static
    {
        $this->dateDernierRetour = $dateDernierRetour;

        return $this;
    }

    public function getSalaireMoyen(): ?string
    {
        return $this->salaireMoyen;
    }

    public function setSalaireMoyen(?string $salaireMoyen): static
    {
        $this->salaireMoyen = $salaireMoyen;

        return $this;
    }

    public function getAllocationConge(): ?string
    {
        return $this->allocationConge;
    }

    public function setAllocationConge(?string $allocationConge): static
    {
        $this->allocationConge = $allocationConge;

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

    public function getTypeConge(): ?string
    {
        return $this->typeConge;
    }

    public function setTypeConge(?string $typeConge): static
    {
        $this->typeConge = $typeConge;

        return $this;
    }
    public function getTypePayementConge(): ?string
    {
        return $this->typePayementConge;
    }

    public function setTypePayementConge(?string $typePayementConge): static
    {
        $this->typePayementConge = $typePayementConge;

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

    public function getDays(): ?string
    {
        return $this->days;
    }

    public function setDays(?string $days): static
    {
        $this->days = $days;

        return $this;
    }

    public function getDaysPlus(): ?string
    {
        return $this->daysPlus;
    }

    public function setDaysPlus(?string $daysPlus): static
    {
        $this->daysPlus = $daysPlus;

        return $this;
    }

    public function getSalaryDue(): ?string
    {
        return $this->salaryDue;
    }

    public function setSalaryDue(?string $salaryDue): static
    {
        $this->salaryDue = $salaryDue;

        return $this;
    }

    public function getWorkMonths(): ?string
    {
        return $this->workMonths;
    }

    public function setWorkMonths(?string $workMonths): static
    {
        $this->workMonths = $workMonths;

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

    public function getOlderDays(): ?string
    {
        return $this->olderDays;
    }

    public function setOlderDays(?string $olderDays): static
    {
        $this->olderDays = $olderDays;

        return $this;
    }

    public function getRemainingVacation(): ?string
    {
        return $this->remainingVacation;
    }

    public function setRemainingVacation(?string $remainingVacation): static
    {
        $this->remainingVacation = $remainingVacation;

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

    /**
     * @return Collection<int, CongePartiel>
     */
    public function getCongePartiels(): Collection
    {
        return $this->congePartiels;
    }

    public function addCongePartiel(CongePartiel $congePartiel): static
    {
        if (!$this->congePartiels->contains($congePartiel)) {
            $this->congePartiels->add($congePartiel);
            $congePartiel->setConge($this);
        }

        return $this;
    }

    public function removeCongePartiel(CongePartiel $congePartiel): static
    {
        if ($this->congePartiels->removeElement($congePartiel)) {
            // set the owning side to null (unless already changed)
            if ($congePartiel->getConge() === $this) {
                $congePartiel->setConge(null);
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

}
