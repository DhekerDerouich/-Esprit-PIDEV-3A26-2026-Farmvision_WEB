<?php
// src/Controller/SecurityController.php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_redirect');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $errors = [];
        $oldData = [];
        
        if ($request->isMethod('POST')) {
            // Récupération des données
            $nom = trim($request->request->get('nom'));
            $prenom = trim($request->request->get('prenom'));
            $email = trim($request->request->get('email'));
            $role = $request->request->get('role', 'AGRICULTEUR');
            $telephone = trim($request->request->get('telephone'));
            $adresse = trim($request->request->get('adresse'));
            $matricule = trim($request->request->get('matricule'));
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');
            
            // Sauvegarde des données pour réaffichage
            $oldData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'role' => $role,
                'telephone' => $telephone,
                'adresse' => $adresse,
                'matricule' => $matricule,
            ];
            
            // Validation nom
            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire';
            } elseif (strlen($nom) < 2) {
                $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
            }
            
            // Validation prénom
            if (empty($prenom)) {
                $errors['prenom'] = 'Le prénom est obligatoire';
            } elseif (strlen($prenom) < 2) {
                $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
            }
            
            // Validation email
            if (empty($email)) {
                $errors['email'] = 'L\'email est obligatoire';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format d\'email invalide';
            } else {
                $existing = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $errors['email'] = 'Cet email est déjà utilisé';
                }
            }
            
            // Validation téléphone (8 chiffres)
            if ($role === 'AGRICULTEUR' && !empty($telephone)) {
                if (!preg_match('/^[0-9]{8}$/', $telephone)) {
                    $errors['telephone'] = 'Le téléphone doit contenir exactement 8 chiffres';
                }
            }
            
            // Validation matricule pour responsable
            if ($role === 'RESPONSABLE_EXPLOITATION' && empty($matricule)) {
                $errors['matricule'] = 'Le matricule est obligatoire pour un responsable';
            } elseif ($role === 'RESPONSABLE_EXPLOITATION' && !preg_match('/^[A-Z]{2,3}-[0-9]{3}$/', $matricule)) {
                $errors['matricule'] = 'Format matricule invalide (ex: ADM-001, RES-002)';
            }
            
            // Validation mot de passe
            if (empty($password)) {
                $errors['password'] = 'Le mot de passe est obligatoire';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères';
            } elseif ($password !== $passwordConfirm) {
                $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
            }
            
            // Si pas d'erreurs, création de l'utilisateur
            if (count($errors) === 0) {
                $user = new Utilisateur();
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setTypeRole($role);
                $user->setActivated(0); // Désactivé par défaut
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                
                // Champs spécifiques selon le rôle
                if ($role === 'AGRICULTEUR') {
                    $user->setTelephone($telephone);
                    $user->setAdresse($adresse);
                } elseif ($role === 'RESPONSABLE_EXPLOITATION') {
                    $user->setMatricule($matricule);
                }
                
                $em->persist($user);
                $em->flush();
                
                $this->addFlash('success', 'Inscription réussie ! Votre compte doit être activé par un administrateur.');
                return $this->redirectToRoute('app_login');
            }
            
            // Ajouter les erreurs flash
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('security/register.html.twig', [
            'errors' => $errors,
            'old' => $oldData,
        ]);
    }
}