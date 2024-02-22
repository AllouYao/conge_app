<?php

namespace App\Service;

use App\Entity\Paiement\Campagne;
use App\Repository\Paiement\CampagneRepository;

class CampagneService
{
   private CampagneRepository $campagneRepository;

    public function __construct(CampagneRepository $campagneRepository)
    {
        $this->campagneRepository = $campagneRepository;

    }

    public function getCampagne(): bool
    {

        $campagneActives = $this->campagneRepository->getCampagneActives();

        if($campagneActives)
        {
            return true;
        }
        return false;
    }

}
