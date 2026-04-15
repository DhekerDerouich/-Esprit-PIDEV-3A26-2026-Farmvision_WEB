<?php
// src/Controller/Admin/UtilisateurController.php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class UtilisateurController extends AbstractController
{
    #[Route('/', name: 'admin_user_index')]
    public function index(Request $request, UtilisateurRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $stats = [
            'total' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult(),
            'admins' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'ADMINISTRATEUR')->getQuery()->getSingleScalarResult(),
            'responsables' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'RESPONSABLE_EXPLOITATION')->getQuery()->getSingleScalarResult(),
            'agriculteurs' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'AGRICULTEUR')->getQuery()->getSingleScalarResult(),
            'actifs' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.activated = 1')->getQuery()->getSingleScalarResult(),
            'inactifs' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.activated = 0')->getQuery()->getSingleScalarResult(),
        ];
        
        return $this->render('admin/user/index.html.twig', [
            'stats' => $stats,
        ]);
    }
    
    #[Route('/ajax/list', name: 'admin_user_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, UtilisateurRepository $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', 'all');
        $status = $request->query->get('status', 'all');
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'DESC');
        
        $qb = $repository->createQueryBuilder('u');
        
        // Filtre recherche
        if (!empty($search)) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Filtre rôle
        if ($role !== 'all') {
            $qb->andWhere('u.type_role = :role')->setParameter('role', $role);
        }
        
        // Filtre statut
        if ($status !== 'all') {
            $qb->andWhere('u.activated = :status')->setParameter('status', $status === 'active' ? 1 : 0);
        }
        
        // Tri
        $allowedSorts = ['id', 'nom', 'prenom', 'email', 'type_role', 'activated', 'date_creation'];
        if (in_array($sort, $allowedSorts)) {
            $qb->orderBy('u.' . $sort, $order);
        } else {
            $qb->orderBy('u.id', 'DESC');
        }
        
        $users = $qb->getQuery()->getResult();
        
        $data = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'telephone' => $user->getTelephone() ?: '-',
                'role' => $user->getTypeRole(),
                'roleLabel' => $this->getRoleLabel($user->getTypeRole()),
                'activated' => $user->isActivated(),
                'dateCreation' => $user->getDateCreation() ? $user->getDateCreation()->format('d/m/Y') : '-',
            ];
        }, $users);
        
        return $this->json([
            'users' => $data,
            'total' => count($data),
        ]);
    }
    
    private function getRoleLabel(string $role): string
    {
        switch($role) {
            case 'ADMINISTRATEUR': return '👑 Administrateur';
            case 'RESPONSABLE_EXPLOITATION': return '📋 Responsable';
            default: return '🌾 Agriculteur';
        }
    }
    
    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UtilisateurRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $formData = [];
        
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $adresse = trim($request->request->get('adresse', ''));
            $typeRole = $request->request->get('type_role', 'AGRICULTEUR');
            $activated = $request->request->get('activated') ? 1 : 0;
            $password = $request->request->get('password', '');
            $matricule = trim($request->request->get('matricule', ''));
            
            $formData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'adresse' => $adresse,
                'type_role' => $typeRole,
                'activated' => $activated,
                'matricule' => $matricule,
            ];
            
            $errors = [];
            
            // Validation du nom
            if (empty($nom)) {
                $errors[] = "Le nom est obligatoire.";
            } elseif (strlen($nom) < 2) {
                $errors[] = "Le nom doit contenir au moins 2 caractères.";
            } elseif (strlen($nom) > 100) {
                $errors[] = "Le nom ne peut pas dépasser 100 caractères.";
            } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $nom)) {
                $errors[] = "Le nom ne peut contenir que des lettres, espaces et tirets.";
            }
            
            // Validation du prénom
            if (empty($prenom)) {
                $errors[] = "Le prénom est obligatoire.";
            } elseif (strlen($prenom) < 2) {
                $errors[] = "Le prénom doit contenir au moins 2 caractères.";
            } elseif (strlen($prenom) > 100) {
                $errors[] = "Le prénom ne peut pas dépasser 100 caractères.";
            } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $prenom)) {
                $errors[] = "Le prénom ne peut contenir que des lettres, espaces et tirets.";
            }
            
            // Validation de l'email
            if (empty($email)) {
                $errors[] = "L'email est obligatoire.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'email '{$email}' n'est pas valide.";
            } else {
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $errors[] = "L'email '{$email}' est déjà utilisé par un autre utilisateur.";
                }
            }
            
            // Validation du téléphone
            if (!empty($telephone)) {
                if (strlen($telephone) > 20) {
                    $errors[] = "Le téléphone ne peut pas dépasser 20 caractères.";
                } elseif (!preg_match("/^[0-9\s\+\-\(\)]+$/", $telephone)) {
                    $errors[] = "Le téléphone ne peut contenir que des chiffres, espaces, +, -, ( et ).";
                }
            }
            
            // Validation de l'adresse
            if (!empty($adresse) && strlen($adresse) > 255) {
                $errors[] = "L'adresse ne peut pas dépasser 255 caractères.";
            }
            
            // Validation du rôle
            $validRoles = ['AGRICULTEUR', 'RESPONSABLE_EXPLOITATION', 'ADMINISTRATEUR'];
            if (!in_array($typeRole, $validRoles)) {
                $errors[] = "Le rôle sélectionné n'est pas valide.";
            }
            
            // Validation du matricule
            if ($typeRole !== 'AGRICULTEUR') {
                if (empty($matricule)) {
                    $errors[] = "Le matricule est obligatoire pour les responsables et administrateurs.";
                } elseif (strlen($matricule) > 50) {
                    $errors[] = "Le matricule ne peut pas dépasser 50 caractères.";
                } elseif (!preg_match("/^[A-Z0-9\-]+$/", $matricule)) {
                    $errors[] = "Le matricule doit contenir uniquement des lettres majuscules, chiffres et tirets. (Ex: ADM-001)";
                } else {
                    $existingMatricule = $userRepository->findOneBy(['matricule' => $matricule]);
                    if ($existingMatricule) {
                        $errors[] = "Le matricule '{$matricule}' est déjà utilisé.";
                    }
                }
            }
            
            // Validation du mot de passe
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
                } elseif (strlen($password) > 255) {
                    $errors[] = "Le mot de passe ne peut pas dépasser 255 caractères.";
                }
            } else {
                $password = $this->generateRandomPassword();
                $formData['generated_password'] = $password;
                $errors[] = "Un mot de passe a été généré automatiquement: {$password} (à communiquer à l'utilisateur)";
            }
            
            if (empty($errors)) {
                $user = new Utilisateur();
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setTelephone($telephone ?: null);
                $user->setAdresse($adresse ?: null);
                $user->setTypeRole($typeRole);
                $user->setActivated($activated);
                $user->setMatricule($matricule ?: null);
                
                if (!empty($password)) {
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                }
                
                $em->persist($user);
                $em->flush();
                
                $this->addFlash('success', 'Utilisateur créé avec succès !');
                return $this->redirectToRoute('admin_user_index');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }
        
        return $this->render('admin/user/new.html.twig', [
            'formData' => $formData
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $user, EntityManagerInterface $em, UtilisateurRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $adresse = trim($request->request->get('adresse', ''));
            $typeRole = $request->request->get('type_role', 'AGRICULTEUR');
            $activated = $request->request->get('activated') ? 1 : 0;
            
            $errors = [];
            
            if (empty($nom)) {
                $errors[] = "Le nom est obligatoire.";
            } elseif (strlen($nom) < 2) {
                $errors[] = "Le nom doit contenir au moins 2 caractères.";
            } elseif (strlen($nom) > 100) {
                $errors[] = "Le nom ne peut pas dépasser 100 caractères.";
            }
            
            if (empty($prenom)) {
                $errors[] = "Le prénom est obligatoire.";
            } elseif (strlen($prenom) < 2) {
                $errors[] = "Le prénom doit contenir au moins 2 caractères.";
            } elseif (strlen($prenom) > 100) {
                $errors[] = "Le prénom ne peut pas dépasser 100 caractères.";
            }
            
            if (empty($email)) {
                $errors[] = "L'email est obligatoire.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'email '{$email}' n'est pas valide.";
            } else {
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $errors[] = "L'email '{$email}' est déjà utilisé par un autre utilisateur.";
                }
            }
            
            if (!empty($telephone) && strlen($telephone) > 20) {
                $errors[] = "Le téléphone ne peut pas dépasser 20 caractères.";
            }
            
            if (!empty($adresse) && strlen($adresse) > 255) {
                $errors[] = "L'adresse ne peut pas dépasser 255 caractères.";
            }
            
            $validRoles = ['AGRICULTEUR', 'RESPONSABLE_EXPLOITATION', 'ADMINISTRATEUR'];
            if (!in_array($typeRole, $validRoles)) {
                $errors[] = "Le rôle sélectionné n'est pas valide.";
            }
            
            if (empty($errors)) {
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setTelephone($telephone ?: null);
                $user->setAdresse($adresse ?: null);
                $user->setTypeRole($typeRole);
                $user->setActivated($activated);
                
                $em->flush();
                $this->addFlash('success', 'Utilisateur modifié avec succès !');
                return $this->redirectToRoute('admin_user_index');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }
        
        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        }
        
        return $this->redirectToRoute('admin_user_index');
    }
    
    #[Route('/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggle(Utilisateur $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user->setActivated($user->isActivated() ? 0 : 1);
        $em->flush();
        $this->addFlash('success', 'Statut utilisateur modifié');
        return $this->redirectToRoute('admin_user_index');
    }
    
    private function generateRandomPassword(int $length = 10): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}