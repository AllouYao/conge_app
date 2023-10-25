<?php

namespace App\Entity\DossierPersonal;

use App\Entity\Settings\Primes;
use App\Repository\DossierPersonal\DetailSalaryRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetailSalaryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DetailSalary
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detailSalaries')]
    private ?Primes $prime = null;

    #[ORM\ManyToOne(inversedBy: 'detailSalaries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salary $salary = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $smigHoraire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $amountPrime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrime(): ?Primes
    {
        return $this->prime;
    }

    public function setPrime(?Primes $prime): static
    {
        $this->prime = $prime;

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

    public function getSmigHoraire(): ?string
    {
        return $this->smigHoraire;
    }

    public function setSmigHoraire(?string $smigHoraire): static
    {
        $this->smigHoraire = $smigHoraire;

        return $this;
    }

    public function getAmountPrime(): ?string
    {
        return $this->amountPrime;
    }

    public function setAmountPrime(?string $amountPrime): static
    {
        $this->amountPrime = $amountPrime;

        return $this;
    }
}
