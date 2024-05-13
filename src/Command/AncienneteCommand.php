<?php

namespace App\Command;

use App\Repository\DossierPersonal\ContractRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:anciennete',
    description: 'Add a short description for your command',
)]
class AncienneteCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContractRepository     $contractRepository
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
        $contracts = $this->contractRepository->findAll();
        $today = new Carbon();
        foreach ($contracts as $contract) {
            $dateEmbauche = $contract->getDateEmbauche();
            $anciennete = $today->diff($dateEmbauche)->days / 360;
            $personal = $contract->getPersonal()->setOlder($anciennete);
            $this->entityManager->persist($personal);
        }
        $this->entityManager->flush();
        $io->success('Ancienneté mise à jour avec succès.');
        return Command::SUCCESS;
    }
}
