<?php
// src/Command/TelegramSetWebhookCommand.php

namespace App\Command;

use App\Service\TelegramBotService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

class TelegramSetWebhookCommand extends Command
{
    protected static $defaultName = 'app:telegram:set-webhook';
    
    private TelegramBotService $telegramService;
    private RouterInterface $router;
    
    public function __construct(TelegramBotService $telegramService, RouterInterface $router)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
        $this->router = $router;
    }
    
    protected function configure(): void
    {
        $this->setDescription('Configure le webhook Telegram');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $telegram = $this->telegramService->getTelegram();
        if (!$telegram) {
            $output->writeln('<error>Telegram non configuré. Vérifiez votre token dans .env</error>');
            return Command::FAILURE;
        }
        
        // Pour le développement local, utilisez polling au lieu de webhook
        $useWebhook = false; // Mettre à true en production
        
        if ($useWebhook) {
            // En production, utilisez votre domaine
            $webhookUrl = 'https://votre-domaine.com/telegram/webhook';
            $output->writeln("<info>Configuration du webhook: $webhookUrl</info>");
            
            try {
                $telegram->setWebhook(['url' => $webhookUrl]);
                $output->writeln('<info>Webhook configuré avec succès !</info>');
            } catch (\Exception $e) {
                $output->writeln('<error>Erreur: ' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }
        } else {
            // En développement, on utilise le polling
            $output->writeln('<comment>Mode développement: Utilisation du polling (getUpdates)</comment>');
            $output->writeln('<info>Pour recevoir les messages, exécutez: php bin/console app:telegram:poll</info>');
        }
        
        return Command::SUCCESS;
    }
}
