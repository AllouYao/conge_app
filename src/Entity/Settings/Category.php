<?php

namespace App\Entity\Settings;

use App\Entity\DossierPersonal\Personal;
use App\Repository\Settings\CategoryRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[HasLifecycleCallbacks]
class Category
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $intitule = null;


    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Personal::class)]
    private Collection $personals;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amount = null;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorySalarie $categorySalarie = null;

    public function __construct()
    {
        $this->personals = new ArrayCollection();
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

    /**
     * @return Collection<int, Personal>
     */
    public function getPersonals(): Collection
    {
        return $this->personals;
    }

    public function addPersonal(Personal $personal): static
    {
        if (!$this->personals->contains($personal)) {
            $this->personals->add($personal);
            $personal->setCategorie($this);
        }

        return $this;
    }

    public function removePersonal(Personal $personal): static
    {
        if ($this->personals->removeElement($personal)) {
            // set the owning side to null (unless already changed)
            if ($personal->getCategorie() === $this) {
                $personal->setCategorie(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->intitule;
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

    public function getCategorySalarie(): ?CategorySalarie
    {
        return $this->categorySalarie;
    }

    public function setCategorySalarie(?CategorySalarie $categorySalarie): static
    {
        $this->categorySalarie = $categorySalarie;

        return $this;
    }
}
