<?php
// src/Command/CheckAlertsCommand.php

namespace App\Command;

use App\Service\AlertesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckAlertsCommand extends Command
{
    protected static $defaultName = 'app:alerts:check';
    
    private AlertesService $alertesService;
    
    public function __construct(AlertesService $alertesService)
    {
        parent::__construct();
        $this->alertesService = $alertesService;
    }
    
    protected function configure(): void
    {
        $this->setDescription('Vérifie et envoie les alertes en temps réel');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $alertes = $this->alertesService->getToutesLesAlertes();
        $urgentes = $this->alertesService->getAlertesUrgentes();
        
        $output->writeln(sprintf('<info>Total: %d alertes</info>', count($alertes)));
        $output->writeln(sprintf('<error>Urgentes: %d alertes</error>', count($urgentes)));
        
        foreach ($urgentes as $alerte) {
            $output->writeln(sprintf('  - %s: %s', $alerte['icone'], $alerte['message']));
        }
        
        return Command::SUCCESS;
    }
}