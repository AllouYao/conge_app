<?php

namespace App\Contract;

use App\Entity\DossierPersonal\Departure;

interface DepartureInterface
{
    public function departurePersonalCharge(Departure $departure): void;

    public function departureEmployeurCharge(Departure $departure): void;

    public function droitIndemnityByDeparture(Departure $departure): void;
}