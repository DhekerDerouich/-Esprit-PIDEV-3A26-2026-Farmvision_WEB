<?php
// src/Command/TelegramPollCommand.php

namespace App\Command;

use App\Service\TelegramBotService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TelegramPollCommand extends Command
{
    protected static $defaultName = 'app:telegram:poll';
    
    private TelegramBotService $telegramService;
    
    public function __construct(TelegramBotService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }
    
    protected function configure(): void
    {
        $this->setDescription('Écoute les messages Telegram (polling)');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $telegram = $this->telegramService->getTelegram();
        if (!$telegram) {
            $output->writeln('<error>Telegram non configuré. Vérifiez votre token.</error>');
            return Command::FAILURE;
        }
        
        $output->writeln('<info>🤖 Bot Telegram démarré en mode polling...</info>');
        $output->writeln('<comment>Appuyez sur Ctrl+C pour arrêter</comment>');
        $output->writeln('');
        
        $lastUpdateId = 0;
        
        while (true) {
            try {
                // Récupère les nouveaux messages
                $updates = $telegram->getUpdates([
                    'offset' => $lastUpdateId + 1,
                    'timeout' => 30,
                    'allowed_updates' => ['message']
                ]);
                
                foreach ($updates as $update) {
                    $message = $update->getMessage();
                    if ($message) {
                        $chatId = $message->getChat()->getId();
                        $text = $message->getText();
                        $firstName = $message->getChat()->getFirstName() ?? 'Utilisateur';
                        
                        $output->writeln("<info>📩 Message de $firstName (Chat ID: $chatId):</info> $text");
                        
                        // Traite la commande
                        $response = $this->telegramService->processCommand($text, $chatId);
                        
                        // Envoie la réponse
                        $this->telegramService->sendMessage($chatId, $response);
                        
                        $output->writeln("<comment>✅ Réponse envoyée</comment>");
                        $output->writeln('');
                    }
                    $lastUpdateId = $update->getUpdateId();
                }
            } catch (\Exception $e) {
                $output->writeln("<error>Erreur: " . $e->getMessage() . "</error>");
                sleep(5);
            }
        }
        
        return Command::SUCCESS;
    }
}
