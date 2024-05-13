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

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRetourConge = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $cumulSalaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $nbPart = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $impotBrut = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $creditImpot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $impotNet = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountCmu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountCnps = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $totatChargePersonal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountIs = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountCr = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountPf = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountTa = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountfpc = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountFpcYear = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountCmuE = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $totalChargeEmployer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $netPayer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $periodeReferences = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $congesOuvrable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $globalMoyen = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $gratificationCorresp = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $olderPersonal = null;

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

    public function getDateRetourConge(): ?\DateTimeInterface
    {
        return $this->dateRetourConge;
    }

    public function setDateRetourConge(?\DateTimeInterface $dateRetourConge): static
    {
        $this->dateRetourConge = $dateRetourConge;

        return $this;
    }

    public function getCumulSalaire(): ?string
    {
        return $this->cumulSalaire;
    }

    public function setCumulSalaire(?string $cumulSalaire): static
    {
        $this->cumulSalaire = $cumulSalaire;

        return $this;
    }

    public function getNbPart(): ?string
    {
        return $this->nbPart;
    }

    public function setNbPart(?string $nbPart): static
    {
        $this->nbPart = $nbPart;

        return $this;
    }

    public function getImpotBrut(): ?string
    {
        return $this->impotBrut;
    }

    public function setImpotBrut(?string $impotBrut): static
    {
        $this->impotBrut = $impotBrut;

        return $this;
    }

    public function getCreditImpot(): ?string
    {
        return $this->creditImpot;
    }

    public function setCreditImpot(?string $creditImpot): static
    {
        $this->creditImpot = $creditImpot;

        return $this;
    }

    public function getImpotNet(): ?string
    {
        return $this->impotNet;
    }

    public function setImpotNet(?string $impotNet): static
    {
        $this->impotNet = $impotNet;

        return $this;
    }

    public function getAmountCmu(): ?string
    {
        return $this->amountCmu;
    }

    public function setAmountCmu(?string $amountCmu): static
    {
        $this->amountCmu = $amountCmu;

        return $this;
    }

    public function getAmountCnps(): ?string
    {
        return $this->amountCnps;
    }

    public function setAmountCnps(?string $amountCnps): static
    {
        $this->amountCnps = $amountCnps;

        return $this;
    }

    public function getTotatChargePersonal(): ?string
    {
        return $this->totatChargePersonal;
    }

    public function setTotatChargePersonal(?string $totatChargePersonal): static
    {
        $this->totatChargePersonal = $totatChargePersonal;

        return $this;
    }

    public function getAmountIs(): ?string
    {
        return $this->amountIs;
    }

    public function setAmountIs(?string $amountIs): static
    {
        $this->amountIs = $amountIs;

        return $this;
    }

    public function getAmountCr(): ?string
    {
        return $this->amountCr;
    }

    public function setAmountCr(?string $amountCr): static
    {
        $this->amountCr = $amountCr;

        return $this;
    }

    public function getAmountPf(): ?string
    {
        return $this->amountPf;
    }

    public function setAmountPf(?string $amountPf): static
    {
        $this->amountPf = $amountPf;

        return $this;
    }

    public function getAmountAt(): ?string
    {
        return $this->amountAt;
    }

    public function setAmountAt(?string $amountAt): static
    {
        $this->amountAt = $amountAt;

        return $this;
    }

    public function getAmountTa(): ?string
    {
        return $this->amountTa;
    }

    public function setAmountTa(?string $amountTa): static
    {
        $this->amountTa = $amountTa;

        return $this;
    }

    public function getAmountfpc(): ?string
    {
        return $this->amountfpc;
    }

    public function setAmountfpc(?string $amountfpc): static
    {
        $this->amountfpc = $amountfpc;

        return $this;
    }

    public function getAmountFpcYear(): ?string
    {
        return $this->amountFpcYear;
    }

    public function setAmountFpcYear(?string $amountFpcYear): static
    {
        $this->amountFpcYear = $amountFpcYear;

        return $this;
    }

    public function getAmountCmuE(): ?string
    {
        return $this->amountCmuE;
    }

    public function setAmountCmuE(?string $amountCmuE): static
    {
        $this->amountCmuE = $amountCmuE;

        return $this;
    }

    public function getTotalChargeEmployer(): ?string
    {
        return $this->totalChargeEmployer;
    }

    public function setTotalChargeEmployer(?string $totalChargeEmployer): static
    {
        $this->totalChargeEmployer = $totalChargeEmployer;

        return $this;
    }

    public function getNetPayer(): ?string
    {
        return $this->netPayer;
    }

    public function setNetPayer(?string $netPayer): static
    {
        $this->netPayer = $netPayer;

        return $this;
    }

    public function getPeriodeReferences(): ?string
    {
        return $this->periodeReferences;
    }

    public function setPeriodeReferences(?string $periodeReferences): static
    {
        $this->periodeReferences = $periodeReferences;

        return $this;
    }

    public function getCongesOuvrable(): ?string
    {
        return $this->congesOuvrable;
    }

    public function setCongesOuvrable(?string $congesOuvrable): static
    {
        $this->congesOuvrable = $congesOuvrable;

        return $this;
    }

    public function getGlobalMoyen(): ?string
    {
        return $this->globalMoyen;
    }

    public function setGlobalMoyen(?string $globalMoyen): static
    {
        $this->globalMoyen = $globalMoyen;

        return $this;
    }

    public function getGratificationCorresp(): ?string
    {
        return $this->gratificationCorresp;
    }

    public function setGratificationCorresp(?string $gratificationCorresp): static
    {
        $this->gratificationCorresp = $gratificationCorresp;

        return $this;
    }

    public function getOlderPersonal(): ?string
    {
        return $this->olderPersonal;
    }

    public function setOlderPersonal(?string $olderPersonal): static
    {
        $this->olderPersonal = $olderPersonal;

        return $this;
    }
}
