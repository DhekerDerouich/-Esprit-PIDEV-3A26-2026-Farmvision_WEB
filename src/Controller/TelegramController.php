<?php
// src/Controller/TelegramController.php

namespace App\Controller;

use App\Service\TelegramBotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TelegramController extends AbstractController
{
    #[Route('/telegram/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function webhook(Request $request, TelegramBotService $telegramService): Response
    {
        $telegram = $telegramService->getTelegram();
        if (!$telegram) {
            return new Response('Telegram non configuré', 500);
        }
        
        try {
            $update = $telegram->commandsHandler(true);
            
            if ($update->getMessage()) {
                $message = $update->getMessage();
                $chatId = $message->getChat()->getId();
                $text = $message->getText();
                
                $response = $telegramService->processCommand($text, $chatId);
                $telegramService->sendMessage($chatId, $response);
            }
        } catch (\Exception $e) {
            return new Response('Erreur: ' . $e->getMessage(), 500);
        }
        
        return new Response('OK', 200);
    }
    
    #[Route('/telegram/set-webhook', name: 'telegram_set_webhook', methods: ['GET'])]
    public function setWebhook(Request $request, TelegramBotService $telegramService): Response
    {
        $telegram = $telegramService->getTelegram();
        if (!$telegram) {
            return $this->json(['error' => 'Telegram non configuré'], 500);
        }
        
        $webhookUrl = $request->getSchemeAndHttpHost() . $this->generateUrl('telegram_webhook');
        
        try {
            $telegram->setWebhook(['url' => $webhookUrl]);
            return $this->json(['success' => true, 'webhook_url' => $webhookUrl]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/telegram/test', name: 'telegram_test', methods: ['GET'])]
    public function test(TelegramBotService $telegramService): Response
    {
        $adminChatId = $_ENV['TELEGRAM_ADMIN_CHAT_ID'] ?? null;
        
        if (!$adminChatId) {
            return $this->json(['error' => 'TELEGRAM_ADMIN_CHAT_ID non configuré'], 500);
        }
        
        $telegramService->sendMessage((int)$adminChatId, "✅ Bot FarmVision opérationnel !");
        
        return $this->json(['status' => 'Message envoyé', 'chat_id' => $adminChatId]);
    }
}
