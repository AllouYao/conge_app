<?php

namespace App\Contract;

use App\Entity\DossierPersonal\Personal;

interface SalaryInterface
{
    public function chargePersonal(Personal $personal): void;

    public function chargeEmployeur(Personal $personal): void;
}