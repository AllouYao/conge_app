<?php

namespace App\Command;

use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\PrimesRepository;
use App\Service\UtimePaiementService;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:gratification-annuel',
    description: 'Add a short description for your command',
)]
class GratificationAnnuelCommand extends Command
{
    public function __construct(
        private readonly PrimesRepository       $primesRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PersonalRepository     $personalRepository,
        private readonly UtimePaiementService   $utimePaiementService,

    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $personal = $this->personalRepository->findAll();
        $tauxGratification = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux();
        foreach ($personal as $item) {
            $olderMonth = $item->getOlder() * 12;
            $service = $this->utimePaiementService->getAmountSalaireBrutAndImposable($item);
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
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
        return Command::SUCCESS;
    }
}
