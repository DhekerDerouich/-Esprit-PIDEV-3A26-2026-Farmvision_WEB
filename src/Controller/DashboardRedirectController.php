<?php
// src/Controller/DashboardRedirectController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardRedirectController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard_redirect')]
    public function redirectDashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $role = $user->getTypeRole();
        
        switch($role) {
            case 'ADMINISTRATEUR':
                // CORRECTION : Utiliser le bon nom de route 'admin_dashboard'
                return $this->redirectToRoute('admin_dashboard');
            case 'RESPONSABLE_EXPLOITATION':
                return $this->redirectToRoute('responsable_dashboard');
            case 'AGRICULTEUR':
                return $this->redirectToRoute('agriculteur_dashboard');
            default:
                return $this->redirectToRoute('front_home');
        }
    }

    #[Route('/agriculteur/dashboard', name: 'agriculteur_dashboard')]
    public function agriculteurDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');
        return $this->render('dashboard/agriculteur.html.twig');
    }

    #[Route('/responsable/dashboard', name: 'responsable_dashboard')]
    public function responsableDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE');
        return $this->render('dashboard/responsable.html.twig');
    }
}