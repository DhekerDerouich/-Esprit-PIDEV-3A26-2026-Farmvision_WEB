<?php

namespace App\Controller;

use App\Security\TwoFactorAuthentication;
use App\Entity\Utilisateur;
use App\CultureParcelle\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestController extends AbstractController
{
    #[Route('/test/email', name: 'test_email')]
    public function testEmail(MailerInterface $mailer): Response
    {
        try {
            $email = (new Email())
                ->from('noreply@farmvision.com')
                ->to('dhekerderouiche3@gmail.com')
                ->subject('Test Email FarmVision')
                ->html('<h1>Test</h1><p>This is a test email.</p>');
            
            $mailer->send($email);
            
            return new Response('Email sent successfully!');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage());
        }
    }

    #[Route('/test/apis', name: 'test_apis')]
    public function testApis(): Response
    {
        return $this->render('test/api_test.html.twig');
    }

    #[Route('/test/weather', name: 'test_weather')]
    public function testWeather(WeatherService $weatherService): JsonResponse
    {
        try {
            // Test with Paris coordinates
            $weatherData = $weatherService->getWeatherByCoordinates(48.8566, 2.3522);
            
            if (!$weatherData) {
                return $this->json([
                    'error' => 'Failed to fetch weather data',
                    'service_response' => null
                ], 503);
            }

            $weatherData['emoji'] = $weatherService->getWeatherEmoji($weatherData['icon']);
            
            return $this->json([
                'success' => true,
                'data' => $weatherData,
                'test_location' => 'Paris (48.8566, 2.3522)'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}