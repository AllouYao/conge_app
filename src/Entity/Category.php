<?php

namespace App\Entity;

use App\Entity\Personal;
use App\Repository\CategoryRepository;
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
    private ?string $libelle = null;


    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Personal::class)]
    private Collection $personals;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amount = null;

    public function __construct()
    {
        $this->personals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->intitule;
    }

    public function setLibelle(string $intitule): static
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

  
}
