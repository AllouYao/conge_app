<?php

namespace App\Entity\DossierPersonal;

use App\Repository\DossierPersonal\AccountBankRepository;
use App\Utils\Horodatage;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountBankRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AccountBank
{
    use Horodatage;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $bankId = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $numCompte = null;

    #[ORM\Column(length: 255)]
    private ?string $rib = null;

    #[ORM\ManyToOne(inversedBy: 'accountBanks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBankId(): ?int
    {
        return $this->bankId;
    }

    public function setBankId(int $bankId): static
    {
        $this->bankId = $bankId;

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

    public function getNumCompte(): ?string
    {
        return $this->numCompte;
    }

    public function setNumCompte(string $numCompte): static
    {
        $this->numCompte = $numCompte;

        return $this;
    }

    public function getRib(): ?string
    {
        return $this->rib;
    }

    public function setRib(string $rib): static
    {
        $this->rib = $rib;

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
}
