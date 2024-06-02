<?php

namespace App\Entity;

use App\Repository\FonctionRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FonctionRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Fonction
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    /**
     * @var Collection<int, Personal>
     */
    #[ORM\ManyToMany(targetEntity: Personal::class, mappedBy: 'fonctions')]
    private Collection $personals;

    public function __construct()
    {
        $this->personals = new ArrayCollection();
    }
    public function __toString(): string
    {
        return $this->libelle;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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
            $personal->addFonction($this);
        }

        return $this;
    }

    public function removePersonal(Personal $personal): static
    {
        if ($this->personals->removeElement($personal)) {
            $personal->removeFonction($this);
        }

        return $this;
    }
}
