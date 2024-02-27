<?php

namespace App\Contract;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;

interface SalaryInterface
{
    public function chargePersonal(Personal $personal, Campagne $campagne): void;

    public function chargeEmployeur(Personal $personal, Campagne $campagne): void;

    public function chargePersonalByDeparture(Personal $personal): void;

    public function chargeEmployeurByDeparture(Personal $personal): void;
}