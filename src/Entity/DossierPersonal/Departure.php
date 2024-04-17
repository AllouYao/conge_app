<?php

namespace App\Entity\DossierPersonal;

use App\Entity\Impots\ChargeEmployeur;
use App\Entity\Impots\ChargePersonals;
use App\Entity\User;
use App\Repository\DossierPersonal\DepartureRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Departure
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isPaied = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $congeAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $dissmissalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $noticeAmount = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;
    #[ORM\Column(length: 255)]
    private ?string $reasonCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaryDue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $gratification = null;

    #[ORM\OneToOne(inversedBy: 'departures', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $fraisFuneraire = null;

    #[ORM\OneToMany(mappedBy: 'departure', targetEntity: ChargePersonals::class)]
    private Collection $chargePersonals;

    #[ORM\OneToMany(mappedBy: 'departure', targetEntity: ChargeEmployeur::class)]
    private Collection $chargeEmployeurs;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountLcmtImposable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountLcmtNoImposable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $totalIndemniteImposable = null;

    #[ORM\ManyToOne(inversedBy: 'user')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $dayOfPresence = null;

    public function __construct()
    {
        $this->chargePersonals = new ArrayCollection();
        $this->chargeEmployeurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isIsPaied(): ?bool
    {
        return $this->isPaied;
    }

    public function setIsPaied(?bool $isPaied): static
    {
        $this->isPaied = $isPaied;

        return $this;
    }

    public function getCongeAmount(): ?string
    {
        return $this->congeAmount;
    }

    public function setCongeAmount(?string $congeAmount): static
    {
        $this->congeAmount = $congeAmount;

        return $this;
    }

    public function getDissmissalAmount(): ?string
    {
        return $this->dissmissalAmount;
    }

    public function setDissmissalAmount(?string $dissmissalAmount): static
    {
        $this->dissmissalAmount = $dissmissalAmount;

        return $this;
    }

    public function getNoticeAmount(): ?string
    {
        return $this->noticeAmount;
    }

    public function setNoticeAmount(?string $noticeAmount): static
    {
        $this->noticeAmount = $noticeAmount;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
    public function getReasonCode(): ?string
    {
        return $this->reasonCode;
    }

    public function setReasonCode(string $reasonCode): static
    {
        $this->reasonCode = $reasonCode;

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

    public function getGratification(): ?string
    {
        return $this->gratification;
    }

    public function setGratification(?string $gratification): static
    {
        $this->gratification = $gratification;

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

    public function getFraisFuneraire(): ?string
    {
        return $this->fraisFuneraire;
    }

    public function setFraisFuneraire(?string $fraisFuneraire): static
    {
        $this->fraisFuneraire = $fraisFuneraire;

        return $this;
    }

    /**
     * @return Collection<int, ChargePersonals>
     */
    public function getChargePersonals(): Collection
    {
        return $this->chargePersonals;
    }

    public function addChargePersonal(ChargePersonals $chargePersonal): static
    {
        if (!$this->chargePersonals->contains($chargePersonal)) {
            $this->chargePersonals->add($chargePersonal);
            $chargePersonal->setDeparture($this);
        }

        return $this;
    }

    public function removeChargePersonal(ChargePersonals $chargePersonal): static
    {
        if ($this->chargePersonals->removeElement($chargePersonal)) {
            // set the owning side to null (unless already changed)
            if ($chargePersonal->getDeparture() === $this) {
                $chargePersonal->setDeparture(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ChargeEmployeur>
     */
    public function getChargeEmployeurs(): Collection
    {
        return $this->chargeEmployeurs;
    }

    public function addChargeEmployeur(ChargeEmployeur $chargeEmployeur): static
    {
        if (!$this->chargeEmployeurs->contains($chargeEmployeur)) {
            $this->chargeEmployeurs->add($chargeEmployeur);
            $chargeEmployeur->setDeparture($this);
        }

        return $this;
    }

    public function removeChargeEmployeur(ChargeEmployeur $chargeEmployeur): static
    {
        if ($this->chargeEmployeurs->removeElement($chargeEmployeur)) {
            // set the owning side to null (unless already changed)
            if ($chargeEmployeur->getDeparture() === $this) {
                $chargeEmployeur->setDeparture(null);
            }
        }

        return $this;
    }

    public function getAmountLcmtImposable(): ?string
    {
        return $this->amountLcmtImposable;
    }

    public function setAmountLcmtImposable(?string $amountLcmtImposable): static
    {
        $this->amountLcmtImposable = $amountLcmtImposable;

        return $this;
    }

    public function getAmountLcmtNoImposable(): ?string
    {
        return $this->amountLcmtNoImposable;
    }

    public function setAmountLcmtNoImposable(?string $amountLcmtNoImposable): static
    {
        $this->amountLcmtNoImposable = $amountLcmtNoImposable;

        return $this;
    }

    public function getTotalIndemniteImposable(): ?string
    {
        return $this->totalIndemniteImposable;
    }

    public function setTotalIndemniteImposable(?string $totalIndemniteImposable): static
    {
        $this->totalIndemniteImposable = $totalIndemniteImposable;

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

    public function getDayOfPresence(): ?string
    {
        return $this->dayOfPresence;
    }

    public function setDayOfPresence(?string $dayOfPresence): static
    {
        $this->dayOfPresence = $dayOfPresence;

        return $this;
    }
}
