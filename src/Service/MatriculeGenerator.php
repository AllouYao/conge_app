<?php

namespace App\Service;

use App\Repository\DossierPersonal\PersonalRepository;

class MatriculeGenerator
{
    private PersonalRepository $personalRepository;

    public function __construct(PersonalRepository $personalRepository)
    {
        $this->personalRepository = $personalRepository;
    }

    public function generateMatricule(): string
    {
        $count = (int)$this->personalRepository->findLastId() ?? 0;
        ++$count;
        $suffix = $this->count($count);
        $date = date('Y');
        return "M$date$suffix";
    }

    private function count($val): string
    {
        switch ($val) {
            case ($val < 10):
                return "0000$val";
            case ($val < 100):
                return "000$val";
            case ($val < 1000):
                return "00$val";
            case ($val < 10000):
                return "0$val";
            case ($val < 100000):
                return $val;
            default:
                throw new \LogicException('Unexpected value');
        }
    }
}