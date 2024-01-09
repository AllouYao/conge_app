<?php

namespace App\Entity\DossierPersonal;

use App\Entity\Settings\Primes;
use App\Repository\DossierPersonal\DetailPrimeSalaryRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetailPrimeSalaryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DetailPrimeSalary
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detailPrimeSalaries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Primes $prime = null;

    #[ORM\ManyToOne(inversedBy: 'detailPrimeSalaries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salary $salary = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $amount = null;

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
