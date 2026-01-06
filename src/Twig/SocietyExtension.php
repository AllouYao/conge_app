<?php

namespace App\Twig;

use App\Repository\SocietyRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SocietyExtension extends AbstractExtension
{
    private SocietyRepository $societyRepository;

    public function __construct(SocietyRepository $societyRepository)
    {
        $this->societyRepository = $societyRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_society', [$this, 'getSociety']),
        ];
    }

    public function getSociety(): ?\App\Entity\Society
    {
        return $this->societyRepository->getFirstResult();
    }
}

