<?php

namespace App\Scheduler;

use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\PrimesRepository;
use App\Service\Personal\ChargesServices;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsScheduledTask('* * * * *')]
final class UpdateGratification
{
    private PersonalRepository $personalRepository;
    private PrimesRepository $primesRepository;
    private EntityManagerInterface $entityManager;
    private ChargesServices $chargesServices;
    private LoggerInterface $logger;

    public function __construct(
        PersonalRepository     $personalRepository,
        PrimesRepository       $primesRepository,
        EntityManagerInterface $entityManager,
        ChargesServices        $chargesServices,
        LoggerInterface        $logger
    )
    {
        $this->personalRepository = $personalRepository;
        $this->primesRepository = $primesRepository;
        $this->entityManager = $entityManager;
        $this->chargesServices = $chargesServices;
        $this->logger = $logger;
    }

    public function __invoke(): void
    {
        $personal = $this->personalRepository->findAll();
        $tauxGratification = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux();
        foreach ($personal as $item) {
            $olderMonth = $item->getOlder() * 12;
            $service = $this->chargesServices->amountSalaireBrutAndImposable($item);
            $salaireCategoriel = $service['salaire_categoriel'];
            if ($olderMonth < 12) {
                $gratification = ((($salaireCategoriel * $tauxGratification) / 100) * ($olderMonth * 30)) / 360;
            } else {
                $gratification = ($salaireCategoriel * $tauxGratification) / 100;
            }
            $item->getSalary()->setGratification($gratification);
            $this->entityManager->persist($item);
        }
        $this->entityManager->flush();
        $this->logger->info(sprintf("Tâche executée avec succès %s", (new \DateTime())->format('d/M/Y')));
    }
}
