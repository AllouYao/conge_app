<?php

namespace App\Entity\DossierPersonal;

use App\Entity\User;
use App\Repository\DossierPersonal\DetailRetenueForfetaireRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetailRetenueForfetaireRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DetailRetenueForfetaire
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detailRetenueForfetaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RetenueForfetaire $retenuForfetaire = null;

    #[ORM\ManyToOne(inversedBy: 'detailRetenueForfetaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salary $salary = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amount = null;

    #[ORM\ManyToOne(inversedBy: 'detailRetenueForfetaires')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Personal $Personal = null;

    #[ORM\ManyToOne(inversedBy: 'detailRetenueForfetaires')]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: ChargePeople::class, inversedBy: 'detailRetenueForfetaires')]
    private Collection $chargePeople;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountEmp = null;

    public function __construct()
    {
        $this->chargePeople = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRetenuForfetaire(): ?RetenueForfetaire
    {
        return $this->retenuForfetaire;
    }

    public function setRetenuForfetaire(?RetenueForfetaire $retenuForfetaire): static
    {
        $this->retenuForfetaire = $retenuForfetaire;

        return $this;
    }

    public function getSalary(): ?Salary
    {
        return $this->salary;
    }

    public function setSalary(?Salary $salary): static
    {
        $this->salary = $salary;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPersonal(): ?Personal
    {
        return $this->Personal;
    }

    public function setPersonal(?Personal $Personal): static
    {
        $this->Personal = $Personal;

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
        }

        return $this;
    }

    public function removeChargePerson(ChargePeople $chargePerson): static
    {
        $this->chargePeople->removeElement($chargePerson);

        return $this;
    }

    public function getAmountEmp(): ?string
    {
        return $this->amountEmp;
    }

    public function setAmountEmp(?string $amountEmp): static
    {
        $this->amountEmp = $amountEmp;

        return $this;
    }
}
