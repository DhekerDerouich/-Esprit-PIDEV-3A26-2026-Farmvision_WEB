<?php
// src/Command/TelegramGetChatIdCommand.php

namespace App\Command;

use App\Service\TelegramBotService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TelegramGetChatIdCommand extends Command
{
    protected static $defaultName = 'app:telegram:get-chat-id';
    
    private TelegramBotService $telegramService;
    
    public function __construct(TelegramBotService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }
    
    protected function configure(): void
    {
        $this->setDescription('Récupère les Chat IDs des utilisateurs du bot');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $telegram = $this->telegramService->getTelegram();
        if (!$telegram) {
            $output->writeln('<error>Telegram non configuré</error>');
            return Command::FAILURE;
        }
        
        try {
            $updates = $telegram->getUpdates();
            
            if (empty($updates)) {
                $output->writeln('<comment>Aucun message reçu. Envoyez /start à votre bot sur Telegram !</comment>');
            } else {
                $output->writeln('<info>Chat IDs trouvés :</info>');
                foreach ($updates as $update) {
                    $message = $update->getMessage();
                    if ($message) {
                        $chatId = $message->getChat()->getId();
                        $username = $message->getChat()->getUsername() ?? 'Pas de username';
                        $firstName = $message->getChat()->getFirstName() ?? '';
                        $output->writeln("• Chat ID: <comment>$chatId</comment> - Utilisateur: $firstName (@$username)");
                    }
                }
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
