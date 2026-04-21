<?php
// src/Controller/Admin/UtilisateurController.php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\ProfileImageUploader;
use App\Service\UserAnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class UtilisateurController extends AbstractController
{
    // =========================================================
    // LISTE PRINCIPALE
    // =========================================================

    #[Route('/', name: 'admin_user_index')]
    public function index(UserAnalyticsService $analytics): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/user/index.html.twig', ['stats' => $analytics->getStats()]);
    }

    // =========================================================
    // AJAX LIST
    // =========================================================

    #[Route('/ajax/list', name: 'admin_user_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, UtilisateurRepository $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $role   = $request->query->get('role', 'all');
        $status = $request->query->get('status', 'all');
        $sort   = $request->query->get('sort', 'id');
        $order  = $request->query->get('order', 'DESC');

        $qb = $repository->createQueryBuilder('u');

        if (!empty($search)) {
            $qb->andWhere('u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }
        if ($role !== 'all') {
            $qb->andWhere('u.type_role = :role')->setParameter('role', $role);
        }
        if ($status === 'active') {
            $qb->andWhere('u.activated = 1');
        } elseif ($status === 'inactive') {
            $qb->andWhere('u.activated = 0');
        } elseif ($status === 'banned') {
            $qb->andWhere('u.banStatus IS NOT NULL');
        }

        $allowed = ['id', 'nom', 'prenom', 'email', 'type_role', 'activated', 'date_creation'];
        $qb->orderBy('u.' . (in_array($sort, $allowed) ? $sort : 'id'), $order === 'ASC' ? 'ASC' : 'DESC');

        $users = $qb->getQuery()->getResult();
        $now   = new \DateTimeImmutable();

        $data = array_map(function (Utilisateur $user) use ($now) {
            $banInfo = null;
            if ($user->getBanStatus() !== null) {
                $expires = $user->getBanExpiresAt();
                $active  = $user->isBanned();
                $banInfo = [
                    'status'    => $user->getBanStatus(),
                    'reason'    => $user->getBanReason(),
                    'expiresAt' => $expires ? $expires->format('d/m/Y H:i') : null,
                    'active'    => $active,
                ];
            }

            return [
                'id'           => $user->getId(),
                'nom'          => $user->getNom(),
                'prenom'       => $user->getPrenom(),
                'email'        => $user->getEmail(),
                'telephone'    => $user->getTelephone() ?: '-',
                'role'         => $user->getTypeRole(),
                'roleLabel'    => $this->getRoleLabel($user->getTypeRole()),
                'activated'    => $user->isActivated(),
                'dateCreation' => $user->getDateCreation()?->format('d/m/Y') ?? '-',
                'ban'          => $banInfo,
            ];
        }, $users);

        return $this->json(['users' => $data, 'total' => count($data)]);
    }

    // =========================================================
    // AJAX SEARCH — autocomplete ban par nom/prénom
    // =========================================================

    #[Route('/ajax/search', name: 'admin_user_ajax_search', methods: ['GET'])]
    public function ajaxSearch(Request $request, UtilisateurRepository $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 2) {
            return $this->json([]);
        }
        $users = $repository->createQueryBuilder('u')
            ->where('u.nom LIKE :q OR u.prenom LIKE :q OR CONCAT(u.prenom, \' \', u.nom) LIKE :q OR CONCAT(u.nom, \' \', u.prenom) LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults(8)
            ->getQuery()->getResult();

        $currentUser = $this->getUser();
        $data = array_map(function (Utilisateur $u) use ($currentUser) {
            return [
                'id'       => $u->getId(),
                'label'    => $u->getPrenom() . ' ' . $u->getNom() . ' — ' . $u->getEmail(),
                'banned'   => $u->isBanned(),
                'isMe'     => ($u === $currentUser),
            ];
        }, $users);

        return $this->json($data);
    }

    // =========================================================
    // BAN
    // =========================================================

    #[Route('/{id}/ban', name: 'admin_user_ban', methods: ['POST'])]
    public function ban(Request $request, Utilisateur $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Ne pas se bannir soi-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', '❌ Vous ne pouvez pas vous bannir vous-même.');
            return $this->redirectToRoute('admin_user_index');
        }

        $duration = $request->request->get('ban_duration'); // '1min','1h','24h','7d','30d','permanent'
        $reason   = trim($request->request->get('ban_reason', ''));

        if (empty($reason)) {
            $this->addFlash('error', '❌ La raison du ban est obligatoire.');
            return $this->redirectToRoute('admin_user_index');
        }

        $user->setBanReason($reason);
        $user->setBannedAt(new \DateTimeImmutable());

        if ($duration === 'permanent') {
            $user->setBanStatus('permanent');
            $user->setBanExpiresAt(null);
        } else {
            $user->setBanStatus('temporary');
            $expires = match ($duration) {
                '1min' => new \DateTimeImmutable('+1 minute'),
                '1h'   => new \DateTimeImmutable('+1 hour'),
                '24h'  => new \DateTimeImmutable('+24 hours'),
                '7d'   => new \DateTimeImmutable('+7 days'),
                '30d'  => new \DateTimeImmutable('+30 days'),
                default => new \DateTimeImmutable('+24 hours'),
            };
            $user->setBanExpiresAt($expires);
        }

        $em->flush();

        $label = $duration === 'permanent' ? 'définitivement' : 'temporairement';
        $this->addFlash('success', "✅ {$user->getPrenom()} {$user->getNom()} a été banni {$label}.");

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/unban', name: 'admin_user_unban', methods: ['POST'])]
    public function unban(Utilisateur $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user->setBanStatus(null);
        $user->setBanReason(null);
        $user->setBanExpiresAt(null);
        $user->setBannedAt(null);
        $em->flush();

        $this->addFlash('success', "✅ {$user->getPrenom()} {$user->getNom()} a été débanni avec succès.");

        return $this->redirectToRoute('admin_user_index');
    }

    // =========================================================
    // DASHBOARD IA USERS
    // =========================================================

    #[Route('/ai-stats', name: 'admin_user_ai_stats')]
    public function aiStats(UserAnalyticsService $analytics): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $stats = $analytics->getIAStats();
        $predictions = $analytics->getPredictions($analytics->getIAStats()['inscriptionsMois'] ?? []);

        return $this->render('admin/user/ai_stats.html.twig', [
            'inscriptionsMois'       => $stats['inscriptionsMois'],
            'inscriptionsMoisLabels' => array_keys($stats['inscriptionsMois']),
            'inscriptionsMoisValues' => array_values($stats['inscriptionsMois']),
            'predictions'            => $predictions,
            'predictionsLabels'      => array_keys($predictions),
            'predictionsValues'      => array_values($predictions),
            'predictionsSum'         => array_sum(array_values($predictions)),
            'genres'                 => $stats['genres'],
            'genresValues'           => array_values($stats['genres']),
            'tranches'               => $stats['tranchesAge'],
            'tranchesLabels'         => array_keys($stats['tranchesAge']),
            'tranchesValues'         => array_values($stats['tranchesAge']),
            'tauxCroissance'         => $stats['croissance'],
            'totalUsers'             => $stats['totalUsers'],
            'previsionProchainMois'  => array_values($predictions)[0] ?? 0,
            'tendance'               => ($stats['croissance'] ?? 0) > 0 ? 'hausse' : (($stats['croissance'] ?? 0) < 0 ? 'baisse' : 'stable'),
        ]);
    }

    // =========================================================
    // CRUD (inchangé)
    // =========================================================

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UtilisateurRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $formData = [];

        if ($request->isMethod('POST')) {
            $nom       = trim($request->request->get('nom', ''));
            $prenom    = trim($request->request->get('prenom', ''));
            $email     = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $adresse   = trim($request->request->get('adresse', ''));
            $typeRole  = $request->request->get('type_role', 'AGRICULTEUR');
            $activated = $request->request->get('activated') ? 1 : 0;
            $password  = $request->request->get('password', '');
            $matricule = trim($request->request->get('matricule', ''));
            $genre     = $request->request->get('genre', '');
            $dateNaissanceStr = $request->request->get('date_naissance', '');

            $formData = compact('nom','prenom','email','telephone','adresse','typeRole','activated','matricule','genre','dateNaissanceStr');
            $errors = [];

            if (empty($nom)) $errors[] = "Le nom est obligatoire.";
            elseif (strlen($nom) < 2) $errors[] = "Le nom doit contenir au moins 2 caractères.";
            if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
            elseif (strlen($prenom) < 2) $errors[] = "Le prénom doit contenir au moins 2 caractères.";
            if (empty($email)) $errors[] = "L'email est obligatoire.";
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide.";
            else {
                if ($userRepository->findOneBy(['email' => $email])) $errors[] = "L'email est déjà utilisé.";
            }
            if (!in_array($typeRole, ['AGRICULTEUR','RESPONSABLE_EXPLOITATION','ADMINISTRATEUR'])) $errors[] = "Rôle invalide.";
            if ($typeRole !== 'AGRICULTEUR') {
                if (empty($matricule)) $errors[] = "Le matricule est obligatoire.";
                elseif ($userRepository->findOneBy(['matricule' => $matricule])) $errors[] = "Le matricule est déjà utilisé.";
            }
            if (empty($password)) {
                $password = $this->generateRandomPassword();
                $formData['generated_password'] = $password;
                $errors[] = "Mot de passe généré automatiquement : {$password}";
            } elseif (strlen($password) < 6) {
                $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
            }

            if (empty($errors)) {
                $user = new Utilisateur();
                $user->setNom($nom)->setPrenom($prenom)->setEmail($email)
                     ->setTelephone($telephone ?: null)->setAdresse($adresse ?: null)
                     ->setTypeRole($typeRole)->setActivated($activated)
                     ->setMatricule($matricule ?: null)
                     ->setGenre(in_array($genre, ['M','F','A']) ? $genre : null);
                if (!empty($dateNaissanceStr)) {
                    $user->setDateNaissance(new \DateTime($dateNaissanceStr));
                }
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Utilisateur créé avec succès !');
                return $this->redirectToRoute('admin_user_index');
            }
            foreach ($errors as $e) { $this->addFlash('error', $e); }
        }

        return $this->render('admin/user/new.html.twig', ['formData' => $formData]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Utilisateur $user, EntityManagerInterface $em, UtilisateurRepository $userRepository, ProfileImageUploader $profileImageUploader, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $errors = [];
        $formData = [];
        $passwordChanged = false;
        $newPassword = null;

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $adresse = trim($request->request->get('adresse', ''));
            $typeRole = $request->request->get('type_role', 'AGRICULTEUR');
            $activated = $request->request->get('activated') ? 1 : 0;
            $photoProfil = $request->files->get('photo_profil');
            $genre = $request->request->get('genre', '');
            
            $newPassword = $request->request->get('new_password', '');

            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire.';
            }
            if (empty($prenom)) {
                $errors['prenom'] = 'Le prénom est obligatoire.';
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'L\'email n\'est pas valide.';
            } else {
                $existing = $userRepository->findOneBy(['email' => $email]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    $errors['email'] = 'Email déjà utilisé.';
                }
            }
            if ($photoProfil instanceof UploadedFile) {
                $err = $profileImageUploader->validate($photoProfil);
                if ($err) {
                    $errors['photo_profil'] = $err;
                }
            }
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    $errors['new_password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
                } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/', $newPassword)) {
                    $errors['new_password'] = 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.';
                } else {
                    $passwordChanged = true;
                }
            }

            if (empty($errors)) {
                $user->setNom($nom)->setPrenom($prenom)->setEmail($email)
                    ->setTelephone($telephone ?: null)->setAdresse($adresse ?: null)
                    ->setTypeRole($typeRole)->setActivated($activated)
                    ->setGenre(in_array($genre, ['M', 'F', 'A']) ? $genre : null);

                if ($photoProfil instanceof UploadedFile) {
                    $user->setPhotoProfil($profileImageUploader->upload($photoProfil, $user->getPhotoProfil()));
                }
                if ($passwordChanged) {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    $this->addFlash('info', 'Nouveau mot de passe défini: '.$newPassword);
                }
                $em->flush();
                $this->addFlash('success', 'Utilisateur modifié avec succès !');
                return $this->redirectToRoute('admin_user_index');
            }
            foreach ($errors as $e) {
                $this->addFlash('error', $e);
            }
        }

return $this->render('admin/user/edit.html.twig', ['user' => $user, 'errors' => $errors, 'formData' => $formData]);
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

    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'ADMINISTRATEUR'          => '👑 Administrateur',
            'RESPONSABLE_EXPLOITATION' => '📋 Responsable',
            default                   => '🌾 Agriculteur',
        };
    }

    private function generateRandomPassword(int $length = 10): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
        $p = '';
        for ($i = 0; $i < $length; $i++) {
            $p .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $p;
    }
}