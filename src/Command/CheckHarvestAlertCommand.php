<?php

namespace App\Command;

use App\CultureParcelle\Service\HarvestAlertService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-harvest-alert',
    description: 'Check for cultures that need harvest alerts and send notifications'
)]
class CheckHarvestAlertCommand extends Command
{
    public function __construct(
        private HarvestAlertService $harvestAlertService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Checking Harvest Alerts');
        
        $alertCultures = $this->harvestAlertService->getCulturesNeedingHarvestAlert();
        
        if (empty($alertCultures)) {
            $io->success('No cultures need harvest alerts at this time.');
            return Command::SUCCESS;
        }
        
        $io->info(sprintf('Found %d culture(s) needing harvest alerts', count($alertCultures)));
        
        foreach ($alertCultures as $alertData) {
            $culture = $alertData['culture'];
            $days = $alertData['daysUntilHarvest'];
            
            $sent = $this->harvestAlertService->sendHarvestAlert($culture, $days);
            
            if ($sent) {
                $io->writeln(sprintf(
                    '✓ Alert sent for "%s" (harvest in %d day(s))',
                    $culture->getNomCulture(),
                    $days
                ));
            } else {
                $io->warning(sprintf(
                    'Failed to send alert for "%s"',
                    $culture->getNomCulture()
                ));
            }
        }
        
        $io->success('Harvest alert check completed!');
        
        return Command::SUCCESS;
    }
}
