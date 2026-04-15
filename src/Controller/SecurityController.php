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

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRouteBasedOnRole();
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
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $errors = [];
        $oldData = [];
        
        if ($request->isMethod('POST')) {
            // Récupération des données
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = trim($request->request->get('email', ''));
            $role = $request->request->get('role', 'AGRICULTEUR');
            $telephone = trim($request->request->get('telephone', ''));
            $adresse = trim($request->request->get('adresse', ''));
            $matricule = trim($request->request->get('matricule', ''));
            $password = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirm', '');
            
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
            
            // ========== VALIDATION PHP PERSONNALISÉE ==========
            
            // Validation du NOM
            if (empty($nom)) {
                $errors['nom'] = '❌ Le nom est obligatoire.';
            } elseif (strlen($nom) < 2) {
                $errors['nom'] = '❌ Le nom doit contenir au moins 2 caractères.';
            } elseif (strlen($nom) > 100) {
                $errors['nom'] = '❌ Le nom ne peut pas dépasser 100 caractères.';
            } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $nom)) {
                $errors['nom'] = '❌ Le nom ne peut contenir que des lettres, espaces et tirets.';
            } else {
                $errors['nom'] = '✅ Nom valide !';
            }
            
            // Validation du PRÉNOM
            if (empty($prenom)) {
                $errors['prenom'] = '❌ Le prénom est obligatoire.';
            } elseif (strlen($prenom) < 2) {
                $errors['prenom'] = '❌ Le prénom doit contenir au moins 2 caractères.';
            } elseif (strlen($prenom) > 100) {
                $errors['prenom'] = '❌ Le prénom ne peut pas dépasser 100 caractères.';
            } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $prenom)) {
                $errors['prenom'] = '❌ Le prénom ne peut contenir que des lettres, espaces et tirets.';
            } else {
                $errors['prenom'] = '✅ Prénom valide !';
            }
            
            // Validation de l'EMAIL
            if (empty($email)) {
                $errors['email'] = '❌ L\'email est obligatoire.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = '❌ Format d\'email invalide. Exemple: nom@domaine.com';
            } elseif (!preg_match('/@.*\.(com|tn|fr|net|org)$/', $email)) {
                $errors['email'] = '❌ L\'email doit se terminer par .com, .tn, .fr, .net ou .org.';
            } else {
                // Vérifier si l'email existe déjà
                $existing = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $errors['email'] = '❌ Cet email est déjà utilisé. Veuillez en choisir un autre.';
                } else {
                    $errors['email'] = '✅ Email valide !';
                }
            }
            
            // Validation du TÉLÉPHONE (pour agriculteur)
            if ($role === 'AGRICULTEUR') {
                if (!empty($telephone)) {
                    if (!preg_match('/^[0-9]{8}$/', $telephone)) {
                        $errors['telephone'] = '❌ Le téléphone doit contenir exactement 8 chiffres.';
                    } else {
                        $errors['telephone'] = '✅ Téléphone valide !';
                    }
                } else {
                    $errors['telephone'] = 'ℹ️ Le téléphone est optionnel.';
                }
            }
            
            // Validation du MATRICULE (pour responsable)
            if ($role === 'RESPONSABLE_EXPLOITATION') {
                if (empty($matricule)) {
                    $errors['matricule'] = '❌ Le matricule est obligatoire pour un responsable d\'exploitation.';
                } elseif (!preg_match('/^[A-Z]{2,3}-[0-9]{3}$/', $matricule)) {
                    $errors['matricule'] = '❌ Format matricule invalide. Exemple: ADM-001, RES-002, DIR-003';
                } else {
                    $errors['matricule'] = '✅ Matricule valide !';
                }
            }
            
            // Validation du MOT DE PASSE
            if (empty($password)) {
                $errors['password'] = '❌ Le mot de passe est obligatoire.';
            } elseif (strlen($password) < 6) {
                $errors['password'] = '❌ Le mot de passe doit contenir au moins 6 caractères.';
            } elseif (strlen($password) > 255) {
                $errors['password'] = '❌ Le mot de passe est trop long (maximum 255 caractères).';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/', $password)) {
                $errors['password'] = '❌ Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.';
            } else {
                $errors['password'] = '✅ Mot de passe valide !';
            }
            
            // Validation de la CONFIRMATION du mot de passe
            if ($password !== $passwordConfirm) {
                $errors['password_confirm'] = '❌ Les mots de passe ne correspondent pas.';
            } elseif (!empty($password) && $password === $passwordConfirm) {
                $errors['password_confirm'] = '✅ Confirmation valide !';
            }
            
            // Filtrer les messages de succès pour ne garder que les erreurs
            $finalErrors = [];
            foreach ($errors as $key => $value) {
                if (strpos($value, '✅') === false && strpos($value, 'ℹ️') === false) {
                    $finalErrors[$key] = $value;
                }
            }
            
            // Si pas d'erreurs, création de l'utilisateur
            if (count($finalErrors) === 0) {
                $user = new Utilisateur();
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setTypeRole($role);
                $user->setActivated(0); // Désactivé par défaut
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                
                if ($role === 'AGRICULTEUR') {
                    $user->setTelephone($telephone);
                    $user->setAdresse($adresse);
                } elseif ($role === 'RESPONSABLE_EXPLOITATION') {
                    $user->setMatricule($matricule);
                }
                
                $em->persist($user);
                $em->flush();
                
                $this->addFlash('success', '✅ Inscription réussie ! Votre compte doit être activé par un administrateur.');
                return $this->redirectToRoute('app_login');
            }
            
            // Ajouter les erreurs flash
            foreach ($finalErrors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('security/register.html.twig', [
            'errors' => $errors,
            'old' => $oldData,
        ]);
    }

    private function redirectToRouteBasedOnRole(): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        switch($user->getTypeRole()) {
            case 'ADMINISTRATEUR': return $this->redirectToRoute('admin_dashboard');
            case 'RESPONSABLE_EXPLOITATION': return $this->redirectToRoute('responsable_dashboard');
            default: return $this->redirectToRoute('agriculteur_dashboard');
        }
    }
}