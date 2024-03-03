<?php

namespace App\Scheduler;

use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\PrimesRepository;
use App\Service\Personal\ChargesServices;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule(name: 'default')]
final class UpdateGratificationHandler implements ScheduleProviderInterface
{
    private PersonalRepository $personalRepository;
    private PrimesRepository $primesRepository;
    private EntityManagerInterface $entityManager;
    private ChargesServices $chargesServices;

    public function __construct(
        PersonalRepository     $personalRepository,
        PrimesRepository       $primesRepository,
        EntityManagerInterface $entityManager,
        ChargesServices        $chargesServices
    )
    {
        $this->personalRepository = $personalRepository;
        $this->primesRepository = $primesRepository;
        $this->entityManager = $entityManager;
        $this->chargesServices = $chargesServices;
    }

    public function __invoke(UpdateGratification $message): void
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
        //$this->logger->info(sprintf("Tâche executée avec succès %s", (new \DateTime())->format('d/M/Y')));
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->add(
                RecurringMessage::cron('*/1 * * * *', new UpdateGratification())
            );
    }
}
