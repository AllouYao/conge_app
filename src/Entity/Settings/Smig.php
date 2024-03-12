<?php

namespace App\Entity\Settings;

use App\Repository\Settings\SmigRepository;
use App\Utils\Horodatage;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SmigRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Smig
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\OneToMany(mappedBy: 'smigs', targetEntity: CategorySalarie::class)]
    private Collection $categorySalaries;

    public function __construct()
    {
        $this->categorySalaries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

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

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, CategorySalarie>
     */
    public function getCategorySalaries(): Collection
    {
        return $this->categorySalaries;
    }

    public function addCategorySalary(CategorySalarie $categorySalary): static
    {
        if (!$this->categorySalaries->contains($categorySalary)) {
            $this->categorySalaries->add($categorySalary);
            $categorySalary->setSmigs($this);
        }

        return $this;
    }

    public function removeCategorySalary(CategorySalarie $categorySalary): static
    {
        if ($this->categorySalaries->removeElement($categorySalary)) {
            // set the owning side to null (unless already changed)
            if ($categorySalary->getSmigs() === $this) {
                $categorySalary->setSmigs(null);
            }
        }

        return $this;
    }
}
