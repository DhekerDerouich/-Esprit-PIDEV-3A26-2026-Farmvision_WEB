<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\ProfileImageUploader;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'profile_show')]
    public function show(QRCodeService $qrCodeService): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        try {
            $profileQrCode = $qrCodeService->generateUserQrCodeBase64($user);
            $vcardQrCode = $qrCodeService->generateVCardBase64($user);
        } catch (\Exception $e) {
            $profileQrCode = '';
            $vcardQrCode = '';
            $this->addFlash('warning', 'Impossible de générer les QR Codes: ' . $e->getMessage());
        }

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'profileQrCode' => $profileQrCode,
            'vcardQrCode' => $vcardQrCode,
        ]);
    }

    #[Route('/public/{id}', name: 'profile_public')]
    public function publicProfile(int $id, UtilisateurRepository $userRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        return $this->render('profile/public.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/qrcode/download', name: 'profile_qrcode_download')]
    public function downloadQrCode(QRCodeService $qrCodeService): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $vcardBase64 = $qrCodeService->generateVCardBase64($user);
        
        $qrCodeDir = $this->getParameter('kernel.project_dir') . '/public/uploads/qrcodes';
        
        if (!is_dir($qrCodeDir)) {
            mkdir($qrCodeDir, 0755, true);
        }

        $filename = 'vcard_' . $user->getId() . '.png';
        $fullPath = $qrCodeDir . '/' . $filename;

        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $vcardBase64));
        file_put_contents($fullPath, $imageData);

        return $this->file($fullPath, 'qrcode_' . $user->getNom() . '_' . $user->getPrenom() . '.png');
    }

    #[Route('/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UtilisateurRepository $userRepository,
        ProfileImageUploader $profileImageUploader,
    ): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $errors = [];
        
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = trim($request->request->get('email', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $adresse = trim($request->request->get('adresse', ''));
            $photoProfil = $request->files->get('photo_profil');
            $currentPassword = $request->request->get('current_password', '');
            $newPassword = $request->request->get('new_password', '');
            $confirmPassword = $request->request->get('confirm_password', '');
            
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
            }
            
            // Validation de l'EMAIL
            if (empty($email)) {
                $errors['email'] = '❌ L\'email est obligatoire.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = '❌ Format d\'email invalide. Exemple: nom@domaine.com';
            } else {
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser instanceof Utilisateur && $existingUser->getId() !== $user->getId()) {
                    $errors['email'] = '❌ Cet email est déjà utilisé par un autre compte.';
                }
            }
            
            // Validation du TÉLÉPHONE (optionnel)
            if (!empty($telephone)) {
                if (!preg_match('/^[0-9]{8}$/', $telephone)) {
                    $errors['telephone'] = '❌ Le téléphone doit contenir exactement 8 chiffres.';
                }
            }
            
            // Validation de l'ADRESSE (optionnel)
            if (!empty($adresse) && strlen($adresse) > 255) {
                $errors['adresse'] = '❌ L\'adresse ne peut pas dépasser 255 caractères.';
            }

            if ($photoProfil instanceof UploadedFile) {
                $photoError = $profileImageUploader->validate($photoProfil);
                if ($photoError !== null) {
                    $errors['photo_profil'] = $photoError;
                }
            }
            
            // Changement de mot de passe
            $passwordChanged = false;
            if (!empty($newPassword) || !empty($confirmPassword) || !empty($currentPassword)) {
                $hasLocalPassword = !empty($user->getPassword());

                if ($hasLocalPassword && empty($currentPassword)) {
                    $errors['current_password'] = '❌ Veuillez entrer votre mot de passe actuel.';
                } elseif ($hasLocalPassword && !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $errors['current_password'] = '❌ Mot de passe actuel incorrect.';
                }

                if (empty($newPassword)) {
                    $errors['new_password'] = '❌ Le nouveau mot de passe est obligatoire.';
                } elseif (strlen($newPassword) < 6) {
                    $errors['new_password'] = '❌ Le nouveau mot de passe doit contenir au moins 6 caractères.';
                } elseif (strlen($newPassword) > 255) {
                    $errors['new_password'] = '❌ Le nouveau mot de passe est trop long.';
                } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/', $newPassword)) {
                    $errors['new_password'] = '❌ Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.';
                } elseif ($newPassword !== $confirmPassword) {
                    $errors['confirm_password'] = '❌ Les mots de passe ne correspondent pas.';
                } else {
                    $passwordChanged = true;
                }
            }
            
            // Si pas d'erreurs, mise à jour
            if (count($errors) === 0) {
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setTelephone($telephone ?: null);
                $user->setAdresse($adresse ?: null);

                if ($photoProfil instanceof UploadedFile) {
                    $user->setPhotoProfil($profileImageUploader->upload($photoProfil, $user->getPhotoProfil()));
                }
                
                if ($passwordChanged) {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    $this->addFlash('success', '✅ Profil mis à jour avec succès ! Votre mot de passe a été modifié.');
                } else {
                    $this->addFlash('success', '✅ Profil mis à jour avec succès !');
                }
                
                $em->flush();
                return $this->redirectToRoute('profile_show');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'errors' => $errors,
            'hasLocalPassword' => !empty($user->getPassword()),
        ]);
    }
}
