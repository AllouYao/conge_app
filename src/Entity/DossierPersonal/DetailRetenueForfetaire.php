<?php

namespace App\Entity\DossierPersonal;

use App\Repository\DossierPersonal\DetailRetenueForfetaireRepository;
use App\Utils\Horodatage;
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
}
