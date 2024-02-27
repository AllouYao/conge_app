<?php

namespace App\Entity\DossierPersonal;

use App\Entity\User;
use App\Repository\DossierPersonal\ChargePeopleRepository;
use App\Utils\Horodatage;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChargePeopleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ChargePeople
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $birthday = null;

    #[ORM\Column(length: 255)]
    private ?string $gender = null;

    #[ORM\Column(length: 255)]
    private ?string $numPiece = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\ManyToOne(inversedBy: 'chargePeople')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    #[ORM\ManyToOne(inversedBy: 'chargepeople')]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: DetailRetenueForfetaire::class, mappedBy: 'chargePeople')]
    private Collection $detailRetenueForfetaires;

    public function __construct()
    {
        $this->detailRetenueForfetaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBirthday(): ?DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getNumPiece(): ?string
    {
        return $this->numPiece;
    }

    public function setNumPiece(string $numPiece): static
    {
        $this->numPiece = $numPiece;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

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
            $detailRetenueForfetaire->addChargePerson($this);
        }

        return $this;
    }

    public function removeDetailRetenueForfetaire(DetailRetenueForfetaire $detailRetenueForfetaire): static
    {
        if ($this->detailRetenueForfetaires->removeElement($detailRetenueForfetaire)) {
            $detailRetenueForfetaire->removeChargePerson($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }


}
