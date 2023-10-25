<?php

namespace App\Entity\Settings;

use App\Repository\Settings\TypeAventageRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeAventageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TypeAventage
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\OneToMany(mappedBy: 'typeAventage', targetEntity: Aventage::class, orphanRemoval: true)]
    private Collection $aventages;

    public function __construct()
    {
        $this->aventages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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
     * @return Collection<int, Aventage>
     */
    public function getAventages(): Collection
    {
        return $this->aventages;
    }

    public function addAventage(Aventage $aventage): static
    {
        if (!$this->aventages->contains($aventage)) {
            $this->aventages->add($aventage);
            $aventage->setTypeAventage($this);
        }

        return $this;
    }

    public function removeAventage(Aventage $aventage): static
    {
        if ($this->aventages->removeElement($aventage)) {
            // set the owning side to null (unless already changed)
            if ($aventage->getTypeAventage() === $this) {
                $aventage->setTypeAventage(null);
            }
        }

        return $this;
    }
}
