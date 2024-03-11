<?php

namespace App\Entity\DevPaie;

use App\Repository\DevPaie\WorkTimeRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Utils\Horodatage;


#[ORM\Entity(repositoryClass: WorkTimeRepository::class)]
#[ORM\HasLifecycleCallbacks]

class WorkTime
{
    use Horodatage;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $hourValue = null;

    #[ORM\Column(nullable: true)]
    private ?string $code = null;

    #[ORM\Column(nullable: true)]
    private ?int $rateValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getHourValue(): ?int
    {
        return $this->hourValue;
    }

    public function setHourValue(?int $hourValue): static
    {
        $this->hourValue = $hourValue;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getRateValue(): ?int
    {
        return $this->rateValue;
    }

    public function setRateValue(?int $rateValue): static
    {
        $this->rateValue = $rateValue;

        return $this;
    }
}
