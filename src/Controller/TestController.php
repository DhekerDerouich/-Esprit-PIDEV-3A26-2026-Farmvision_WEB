<?php

namespace App\Controller;

use App\Security\TwoFactorAuthentication;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
}