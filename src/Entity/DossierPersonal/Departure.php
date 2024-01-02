<?php

namespace App\Entity\DossierPersonal;

use App\Repository\DossierPersonal\DepartureRepository;
use App\Utils\Horodatage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Departure
{

    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isPaied = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $congeAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $dissmissalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $noticeAmount = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $salaryDue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $gratification = null;

    #[ORM\OneToOne(inversedBy: 'departures', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isIsPaied(): ?bool
    {
        return $this->isPaied;
    }

    public function setIsPaied(?bool $isPaied): static
    {
        $this->isPaied = $isPaied;

        return $this;
    }

    public function getCongeAmount(): ?string
    {
        return $this->congeAmount;
    }

    public function setCongeAmount(?string $congeAmount): static
    {
        $this->congeAmount = $congeAmount;

        return $this;
    }

    public function getDissmissalAmount(): ?string
    {
        return $this->dissmissalAmount;
    }

    public function setDissmissalAmount(?string $dissmissalAmount): static
    {
        $this->dissmissalAmount = $dissmissalAmount;

        return $this;
    }

    public function getNoticeAmount(): ?string
    {
        return $this->noticeAmount;
    }

    public function setNoticeAmount(?string $noticeAmount): static
    {
        $this->noticeAmount = $noticeAmount;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getSalaryDue(): ?string
    {
        return $this->salaryDue;
    }

    public function setSalaryDue(?string $salaryDue): static
    {
        $this->salaryDue = $salaryDue;

        return $this;
    }

    public function getGratification(): ?string
    {
        return $this->gratification;
    }

    public function setGratification(?string $gratification): static
    {
        $this->gratification = $gratification;

        return $this;
    }

    public function getPersonal(): ?Personal
    {
        return $this->personal;
    }

    public function setPersonal(Personal $personal): static
    {
        $this->personal = $personal;

        return $this;
    }
}
