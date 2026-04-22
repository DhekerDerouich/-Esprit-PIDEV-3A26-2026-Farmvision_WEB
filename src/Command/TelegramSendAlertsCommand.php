<?php
// src/Command/TelegramSendAlertsCommand.php

namespace App\Command;

use App\Repository\MaintenanceRepository;
use App\Service\TelegramBotService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TelegramSendAlertsCommand extends Command
{
    protected static $defaultName = 'app:telegram:send-alerts';
    
    private TelegramBotService $telegramService;
    private MaintenanceRepository $maintenanceRepo;
    
    public function __construct(
        TelegramBotService $telegramService,
        MaintenanceRepository $maintenanceRepo
    ) {
        parent::__construct();
        $this->telegramService = $telegramService;
        $this->maintenanceRepo = $maintenanceRepo;
    }
    
    protected function configure(): void
    {
        $this->setDescription('Envoie les alertes de maintenance par Telegram');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTime();
        $maintenances = $this->maintenanceRepo->findAll();
        
        // Liste des chats à notifier (à stocker en BDD ou config)
        // Pour obtenir votre chat_id, utilisez la commande: php bin/console app:telegram:get-chat-id
        $adminChatId = $_ENV['TELEGRAM_ADMIN_CHAT_ID'] ?? null;
        
        if (!$adminChatId) {
            $output->writeln('<error>TELEGRAM_ADMIN_CHAT_ID non configuré dans .env</error>');
            return Command::FAILURE;
        }
        
        $chats = [(int)$adminChatId];
        $sentCount = 0;
        
        foreach ($maintenances as $m) {
            if ($m->getStatut() === 'Planifiée') {
                $diff = $today->diff($m->getDateMaintenance())->days;
                
                // Alerte J-7
                if ($diff == 7) {
                    $alert = [
                        'equipement' => $m->getEquipement()->getNom(),
                        'type' => $m->getTypeMaintenance(),
                        'date' => $m->getDateMaintenance()->format('d/m/Y'),
                        'jours' => $diff,
                        'description' => $m->getDescription()
                    ];
                    
                    foreach ($chats as $chatId) {
                        $this->telegramService->sendMaintenanceAlert($chatId, $alert);
                        $sentCount++;
                    }
                }
                
                // Alerte J-1
                if ($diff == 1) {
                    $alert = [
                        'equipement' => $m->getEquipement()->getNom(),
                        'type' => $m->getTypeMaintenance(),
                        'date' => $m->getDateMaintenance()->format('d/m/Y'),
                        'jours' => $diff,
                        'description' => $m->getDescription()
                    ];
                    
                    foreach ($chats as $chatId) {
                        $this->telegramService->sendMaintenanceAlert($chatId, $alert);
                        $sentCount++;
                    }
                }
            }
        }
        
        $output->writeln("<info>$sentCount alertes Telegram envoyées !</info>");
        return Command::SUCCESS;
    }
}
