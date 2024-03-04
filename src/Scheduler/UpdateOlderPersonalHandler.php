<?php

namespace App\Scheduler;

use App\Repository\DossierPersonal\PersonalRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsMessageHandler]
final class UpdateOlderPersonalHandler
{
    private PersonalRepository $personalRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(PersonalRepository $personalRepository, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->personalRepository = $personalRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function __invoke(UpdateOlderPersonal $message): void
    {
        $personals = $this->personalRepository->findAll();
        $today = new Carbon();
        foreach ($personals as $personal) {
            $dateEmbauche = $personal->getContract()->getDateEmbauche();
            $anciennete = $today->diff($dateEmbauche)->days / 360;
            $personal->setOlder($anciennete);
            $this->entityManager->persist($personal);
        }
        $this->entityManager->flush();
        $this->logger->info('Tâche executée avec succès %s');
    }
}
