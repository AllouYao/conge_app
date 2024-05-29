<?php

namespace App\Entity;

use App\Utils\Horodatage;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SocietyRepository;

#[ORM\Entity(repositoryClass: SocietyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Society
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $raisonSocial = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $forme = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroCC = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siege = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRaisonSocial(): ?string
    {
        return $this->raisonSocial;
    }

    public function setRaisonSocial(string $raisonSocial): self
    {
        $this->raisonSocial = $raisonSocial;

        return $this;
    }

    public function getForme(): ?string
    {
        return $this->forme;
    }

    public function setForme(?string $forme): self
    {
        $this->forme = $forme;

        return $this;
    }

    public function getActivity(): ?string
    {
        return $this->activity;
    }

    public function setActivity(?string $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    public function getNumeroCC(): ?string
    {
        return $this->numeroCC;
    }

    public function setNumeroCC(?string $numeroCC): self
    {
        $this->numeroCC = $numeroCC;

        return $this;
    }

    public function getSiege(): ?string
    {
        return $this->siege;
    }

    public function setSiege(?string $siege): self
    {
        $this->siege = $siege;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }
    
    public function __toString(): string {
        return $this->getRaisonSocial();
    }

}
