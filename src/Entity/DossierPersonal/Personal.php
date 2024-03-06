<?php

namespace App\Entity\DossierPersonal;

use App\Entity\DevPaie\Operation;
use App\Entity\DossierPersonal\HeureSup;
use App\Entity\ElementVariable\VariablePaie;
use App\Entity\Impots\ChargeEmployeur;
use App\Entity\Impots\ChargePersonals;
use App\Entity\Paiement\Campagne;
use App\Entity\Paiement\Payroll;
use App\Entity\Settings\Category;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PersonalRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['matricule'])]
class Personal
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $matricule = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $genre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieuNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refCNPS = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $piece = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refPiece = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\ManyToOne(inversedBy: 'personals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $categorie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $conjoint = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numCertificat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numExtraitActe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etatCivil = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $niveauFormation = null;

    #[ORM\OneToOne(mappedBy: 'personal', cascade: ['persist', 'remove'])]
    private ?Contract $contract = null;

    #[ORM\OneToOne(mappedBy: 'personal', cascade: ['persist', 'remove'])]
    private ?Affectation $affectation = null;

    #[ORM\OneToOne(mappedBy: 'personal', cascade: ['persist', 'remove'])]
    private ?Salary $salary = null;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: ChargePeople::class, orphanRemoval: true)]
    private Collection $chargePeople;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: AccountBank::class, orphanRemoval: true)]
    private Collection $accountBanks;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modePaiement = null;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: ChargePersonals::class)]
    private Collection $chargePersonals;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: ChargeEmployeur::class)]
    private Collection $chargeEmployeurs;

    #[ORM\ManyToMany(targetEntity: Campagne::class, mappedBy: 'personal')]
    private Collection $campagnes;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: Payroll::class)]
    private Collection $payrolls;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: Conge::class)]
    private Collection $conges;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: HeureSup::class)]
    private Collection $heureSups;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: Absence::class)]
    private Collection $absences;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fonction = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $service = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $older = null;

    #[ORM\OneToOne(mappedBy: 'personal', cascade: ['persist', 'remove'])]
    private ?Departure $departures = null;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: VariablePaie::class)]
    private Collection $variablePaies;

    #[ORM\OneToMany(mappedBy: 'Personal', targetEntity: DetailRetenueForfetaire::class, orphanRemoval: true)]
    private Collection $detailRetenueForfetaires;

    #[ORM\OneToMany(mappedBy: 'personal', targetEntity: Operation::class)]
    private Collection $operations;
    public function __construct()
    {
        $this->chargePeople = new ArrayCollection();
        $this->accountBanks = new ArrayCollection();
        $this->chargePersonals = new ArrayCollection();
        $this->chargeEmployeurs = new ArrayCollection();
        $this->campagnes = new ArrayCollection();
        $this->payrolls = new ArrayCollection();
        $this->conges = new ArrayCollection();
        $this->heureSups = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->variablePaies = new ArrayCollection();
        $this->detailRetenueForfetaires = new ArrayCollection();
        $this->operations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(?string $lieuNaissance): static
    {
        $this->lieuNaissance = $lieuNaissance;

        return $this;
    }

    public function getRefCNPS(): ?string
    {
        return $this->refCNPS;
    }

    public function setRefCNPS(?string $refCNPS): static
    {
        $this->refCNPS = $refCNPS;

        return $this;
    }

    public function getPiece(): ?string
    {
        return $this->piece;
    }

    public function setPiece(string $piece): static
    {
        $this->piece = $piece;

        return $this;
    }

    public function getRefPiece(): ?string
    {
        return $this->refPiece;
    }

    public function setRefPiece(string $refPiece): static
    {
        $this->refPiece = $refPiece;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }


    public function getCategorie(): ?Category
    {
        return $this->categorie;
    }

    public function setCategorie(?Category $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getConjoint(): ?string
    {
        return $this->conjoint;
    }

    public function setConjoint(?string $conjoint): static
    {
        $this->conjoint = $conjoint;

        return $this;
    }

    public function getNumCertificat(): ?string
    {
        return $this->numCertificat;
    }

    public function setNumCertificat(?string $numCertificat): static
    {
        $this->numCertificat = $numCertificat;

        return $this;
    }

    public function getNumExtraitActe(): ?string
    {
        return $this->numExtraitActe;
    }

    public function setNumExtraitActe(?string $numExtraitActe): static
    {
        $this->numExtraitActe = $numExtraitActe;

        return $this;
    }

    public function getEtatCivil(): ?string
    {
        return $this->etatCivil;
    }

    public function setEtatCivil(string $etatCivil): static
    {
        $this->etatCivil = $etatCivil;

        return $this;
    }

    public function getNiveauFormation(): ?string
    {
        return $this->niveauFormation;
    }

    public function setNiveauFormation(?string $niveauFormation): static
    {
        $this->niveauFormation = $niveauFormation;

        return $this;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): static
    {
        // unset the owning side of the relation if necessary
        if ($contract === null && $this->contract !== null) {
            $this->contract->setPersonal(null);
        }

        // set the owning side of the relation if necessary
        if ($contract !== null && $contract->getPersonal() !== $this) {
            $contract->setPersonal($this);
        }

        $this->contract = $contract;

        return $this;
    }

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }

    public function setAffectation(?Affectation $affectation): static
    {
        // unset the owning side of the relation if necessary
        if ($affectation === null && $this->affectation !== null) {
            $this->affectation->setPersonal(null);
        }

        // set the owning side of the relation if necessary
        if ($affectation !== null && $affectation->getPersonal() !== $this) {
            $affectation->setPersonal($this);
        }

        $this->affectation = $affectation;

        return $this;
    }

    public function getSalary(): ?Salary
    {
        return $this->salary;
    }

    public function setSalary(Salary $salary): static
    {
        // set the owning side of the relation if necessary
        if ($salary->getPersonal() !== $this) {
            $salary->setPersonal($this);
        }

        $this->salary = $salary;

        return $this;
    }

    /**
     * @return Collection<int, ChargePeople>
     */
    public function getChargePeople(): Collection
    {
        return $this->chargePeople;
    }

    public function addChargePerson(ChargePeople $chargePerson): static
    {
        if (!$this->chargePeople->contains($chargePerson)) {
            $this->chargePeople->add($chargePerson);
            $chargePerson->setPersonal($this);
        }

        return $this;
    }

    public function removeChargePerson(ChargePeople $chargePerson): static
    {
        if ($this->chargePeople->removeElement($chargePerson)) {
            // set the owning side to null (unless already changed)
            if ($chargePerson->getPersonal() === $this) {
                $chargePerson->setPersonal(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccountBank>
     */
    public function getAccountBanks(): Collection
    {
        return $this->accountBanks;
    }

    public function addAccountBank(AccountBank $accountBank): static
    {
        if (!$this->accountBanks->contains($accountBank)) {
            $this->accountBanks->add($accountBank);
            $accountBank->setPersonal($this);
        }

        return $this;
    }

    public function removeAccountBank(AccountBank $accountBank): static
    {
        if ($this->accountBanks->removeElement($accountBank)) {
            // set the owning side to null (unless already changed)
            if ($accountBank->getPersonal() === $this) {
                $accountBank->setPersonal(null);
            }
        }

        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(?string $modePaiement): static
    {
        $this->modePaiement = $modePaiement;

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
            $chargePersonal->setPersonal($this);
        }

        return $this;
    }

    public function removeChargePersonal(ChargePersonals $chargePersonal): static
    {
        if ($this->chargePersonals->removeElement($chargePersonal)) {
            // set the owning side to null (unless already changed)
            if ($chargePersonal->getPersonal() === $this) {
                $chargePersonal->setPersonal(null);
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
            $chargeEmployeur->setPersonal($this);
        }

        return $this;
    }

    public function removeChargeEmployeur(ChargeEmployeur $chargeEmployeur): static
    {
        if ($this->chargeEmployeurs->removeElement($chargeEmployeur)) {
            // set the owning side to null (unless already changed)
            if ($chargeEmployeur->getPersonal() === $this) {
                $chargeEmployeur->setPersonal(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return '( ' . $this->matricule . ' ) - ' . $this->firstName . ' ' . $this->lastName;
    }

    /**
     * @return Collection<int, Campagne>
     */
    public function getCampagnes(): Collection
    {
        return $this->campagnes;
    }

    public function addCampagne(Campagne $campagne): static
    {
        if (!$this->campagnes->contains($campagne)) {
            $this->campagnes->add($campagne);
            $campagne->addPersonal($this);
        }

        return $this;
    }

    public function removeCampagne(Campagne $campagne): static
    {
        if ($this->campagnes->removeElement($campagne)) {
            $campagne->removePersonal($this);
        }

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
            $payroll->setPersonal($this);
        }

        return $this;
    }

    public function removePayroll(Payroll $payroll): static
    {
        if ($this->payrolls->removeElement($payroll)) {
            // set the owning side to null (unless already changed)
            if ($payroll->getPersonal() === $this) {
                $payroll->setPersonal(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Conge>
     */
    public function getConges(): Collection
    {
        return $this->conges;
    }

    public function addConge(Conge $conge): static
    {
        if (!$this->conges->contains($conge)) {
            $this->conges->add($conge);
            $conge->setPersonal($this);
        }

        return $this;
    }

    public function removeConge(Conge $conge): static
    {
        if ($this->conges->removeElement($conge)) {
            // set the owning side to null (unless already changed)
            if ($conge->getPersonal() === $this) {
                $conge->setPersonal(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, HeureSup>
     */
    public function getHeureSups(): Collection
    {
        return $this->heureSups;
    }

    public function addHeureSup(HeureSup $heureSup): static
    {
        if (!$this->heureSups->contains($heureSup)) {
            $this->heureSups->add($heureSup);
            $heureSup->setPersonal($this);
        }

        return $this;
    }

    public function removeHeureSup(HeureSup $heureSup): static
    {
        if ($this->heureSups->removeElement($heureSup)) {
            // set the owning side to null (unless already changed)
            if ($heureSup->getPersonal() === $this) {
                $heureSup->setPersonal(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(Absence $absence): static
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setPersonal($this);
        }

        return $this;
    }

    public function removeAbsence(Absence $absence): static
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getPersonal() === $this) {
                $absence->setPersonal(null);
            }
        }

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getOlder(): ?string
    {
        return $this->older;
    }

    public function setOlder(?string $older): static
    {
        $this->older = $older;

        return $this;
    }

    public function getDepartures(): ?Departure
    {
        return $this->departures;
    }

    public function setDepartures(Departure $departures): static
    {
        // set the owning side of the relation if necessary
        if ($departures->getPersonal() !== $this) {
            $departures->setPersonal($this);
        }

        $this->departures = $departures;

        return $this;
    }

    /**
     * @return Collection<int, VariablePaie>
     */
    public function getVariablePaies(): Collection
    {
        return $this->variablePaies;
    }

    public function addVariablePaie(VariablePaie $variablePaie): static
    {
        if (!$this->variablePaies->contains($variablePaie)) {
            $this->variablePaies->add($variablePaie);
            $variablePaie->setPersonal($this);
        }

        return $this;
    }

    public function removeVariablePaie(VariablePaie $variablePaie): static
    {
        if ($this->variablePaies->removeElement($variablePaie)) {
            // set the owning side to null (unless already changed)
            if ($variablePaie->getPersonal() === $this) {
                $variablePaie->setPersonal(null);
            }
        }

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
            $detailRetenueForfetaire->setPersonal($this);
        }

        return $this;
    }

    public function removeDetailRetenueForfetaire(DetailRetenueForfetaire $detailRetenueForfetaire): static
    {
        if ($this->detailRetenueForfetaires->removeElement($detailRetenueForfetaire)) {
            // set the owning side to null (unless already changed)
            if ($detailRetenueForfetaire->getPersonal() === $this) {
                $detailRetenueForfetaire->setPersonal(null);
            }
        }

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
            $operation->setPersonal($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getPersonal() === $this) {
                $operation->setPersonal(null);
            }
        }

        return $this;
    }
}