<?php

namespace App\Entity;

use App\Entity\Auth\Role;
use App\Entity\DevPaie\Operation;
use App\Entity\DossierPersonal\Absence;
use App\Entity\DossierPersonal\AccountBank;
use App\Entity\DossierPersonal\ChargePeople;
use App\Entity\DossierPersonal\Conge;
use App\Entity\DossierPersonal\Departure;
use App\Entity\DossierPersonal\DetailRetenueForfetaire;
use App\Entity\DossierPersonal\HeureSup;
use App\Entity\Settings\CategorySalarie;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    private Collection $customRoles;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Absence::class)]
    private Collection $absences;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AccountBank::class)]
    private Collection $accountBanks;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Conge::class)]
    private Collection $conges;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: HeureSup::class)]
    private Collection $heureSups;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Departure::class)]
    private Collection $user;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ChargePeople::class)]
    private Collection $chargepeople;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: DetailRetenueForfetaire::class)]
    private Collection $detailRetenueForfetaires;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Operation::class)]
    private Collection $operations;

    #[ORM\ManyToMany(targetEntity: CategorySalarie::class)]
    private Collection $categories;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    public function __construct()
    {
        $this->customRoles = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->accountBanks = new ArrayCollection();
        $this->conges = new ArrayCollection();
        $this->heureSups = new ArrayCollection();
        $this->user = new ArrayCollection();
        $this->chargepeople = new ArrayCollection();
        $this->detailRetenueForfetaires = new ArrayCollection();
        $this->operations = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->customRoles->map(function (Role $role) {
            return $role->getCode();
        })->toArray();

        return array_unique($roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getCustomRoles(): Collection
    {
        return $this->customRoles;
    }

    public function addCustomRole(Role $customRole): static
    {
        if (!$this->customRoles->contains($customRole)) {
            $this->customRoles->add($customRole);
        }

        return $this;
    }

    public function removeCustomRole(Role $customRole): static
    {
        $this->customRoles->removeElement($customRole);

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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
            $absence->setUser($this);
        }

        return $this;
    }

    public function removeAbsence(Absence $absence): static
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getUser() === $this) {
                $absence->setUser(null);
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
            $accountBank->setUser($this);
        }

        return $this;
    }

    public function removeAccountBank(AccountBank $accountBank): static
    {
        if ($this->accountBanks->removeElement($accountBank)) {
            // set the owning side to null (unless already changed)
            if ($accountBank->getUser() === $this) {
                $accountBank->setUser(null);
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
            $conge->setUser($this);
        }

        return $this;
    }

    public function removeConge(Conge $conge): static
    {
        if ($this->conges->removeElement($conge)) {
            // set the owning side to null (unless already changed)
            if ($conge->getUser() === $this) {
                $conge->setUser(null);
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
            $heureSup->setUser($this);
        }

        return $this;
    }

    public function removeHeureSup(HeureSup $heureSup): static
    {
        if ($this->heureSups->removeElement($heureSup)) {
            // set the owning side to null (unless already changed)
            if ($heureSup->getUser() === $this) {
                $heureSup->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Departure>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(Departure $user): static
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
            $user->setUser($this);
        }

        return $this;
    }

    public function removeUser(Departure $user): static
    {
        if ($this->user->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getUser() === $this) {
                $user->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ChargePeople>
     */
    public function getChargepeople(): Collection
    {
        return $this->chargepeople;
    }

    public function addChargeperson(ChargePeople $chargeperson): static
    {
        if (!$this->chargepeople->contains($chargeperson)) {
            $this->chargepeople->add($chargeperson);
            $chargeperson->setUser($this);
        }

        return $this;
    }

    public function removeChargeperson(ChargePeople $chargeperson): static
    {
        if ($this->chargepeople->removeElement($chargeperson)) {
            // set the owning side to null (unless already changed)
            if ($chargeperson->getUser() === $this) {
                $chargeperson->setUser(null);
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
            $detailRetenueForfetaire->setUser($this);
        }

        return $this;
    }

    public function removeDetailRetenueForfetaire(DetailRetenueForfetaire $detailRetenueForfetaire): static
    {
        if ($this->detailRetenueForfetaires->removeElement($detailRetenueForfetaire)) {
            // set the owning side to null (unless already changed)
            if ($detailRetenueForfetaire->getUser() === $this) {
                $detailRetenueForfetaire->setUser(null);
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
            $operation->setUser($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getUser() === $this) {
                $operation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CategorySalarie>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(CategorySalarie $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(CategorySalarie $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }
}
