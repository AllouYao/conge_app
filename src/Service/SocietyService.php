<?php

namespace App\Service;

use App\Entity\Admin\Society;
use App\Repository\Admin\SocietyRepository;

class SocietyService
{
    private SocietyRepository $societyRepository;
    public function __construct(SocietyRepository $societyRepository)
    {
        $this->societyRepository = $societyRepository;

    }

    public function getSociety(): Society
    {
        $society = $this->societyRepository->getFirstResult();

        return $society;
    }
}
