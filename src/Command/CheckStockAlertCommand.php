<?php
// src/Command/CheckStockAlertCommand.php

namespace App\Command;

use App\Repository\StockRepository;
use App\Service\SmsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckStockAlertCommand extends Command
{
    protected static $defaultName = 'app:check-stock-alerts';
    protected static $defaultDescription = 'Vérifie les stocks et envoie des alertes SMS';
    
    private StockRepository $stockRepo;
    private SmsService $smsService;

    public function __construct(StockRepository $stockRepo, SmsService $smsService)
    {
        parent::__construct();
        $this->stockRepo = $stockRepo;
        $this->smsService = $smsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('🔍 Vérification des stocks...');
        
        // Vérifier les stocks bas (moins de 10 unités)
        $lowStocks = $this->stockRepo->findLowStock(10);
        
        foreach ($lowStocks as $stock) {
            $output->writeln("  ⚠️ Stock bas: {$stock->getNomProduit()} ({$stock->getQuantite()} {$stock->getUnite()})");
            // Décommenter pour envoyer de vraies alertes
            // $this->smsService->sendStockAlert('+216XXXXXXXXX', $stock->getNomProduit(), $stock->getQuantite(), 'low_stock');
        }
        
        // Vérifier les produits proches expiration
        $expiringSoon = $this->stockRepo->findExpiringSoon(7);
        
        foreach ($expiringSoon as $stock) {
            $daysLeft = $stock->getJoursAvantExpiration();
            $output->writeln("  ⚠️ Expiration bientôt: {$stock->getNomProduit()} (expire dans {$daysLeft} jours)");
            // Décommenter pour envoyer de vraies alertes
            // $this->smsService->sendStockAlert('+216XXXXXXXXX', $stock->getNomProduit(), $daysLeft, 'expired');
        }
        
        $output->writeln('✅ Vérification terminée.');
        
        return Command::SUCCESS;
    }
}