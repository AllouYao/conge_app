<?php

namespace App\Entity\DossierPersonal;

use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use App\Utils\Horodatage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RetenueForfetaireRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RetenueForfetaire
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $value = null;

    #[ORM\OneToMany(mappedBy: 'retenuForfetaire', targetEntity: DetailRetenueForfetaire::class)]
    private Collection $detailRetenueForfetaires;

    public function __construct()
    {
        $this->detailRetenueForfetaires = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

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
            $detailRetenueForfetaire->setRetenuForfetaire($this);
        }

        return $this;
    }

    public function removeDetailRetenueForfetaire(DetailRetenueForfetaire $detailRetenueForfetaire): static
    {
        if ($this->detailRetenueForfetaires->removeElement($detailRetenueForfetaire)) {
            // set the owning side to null (unless already changed)
            if ($detailRetenueForfetaire->getRetenuForfetaire() === $this) {
                $detailRetenueForfetaire->setRetenuForfetaire(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
