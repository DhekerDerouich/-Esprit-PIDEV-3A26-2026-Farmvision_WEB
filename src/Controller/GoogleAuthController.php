<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleAuthController extends AbstractController
{
    private const SESSION_KEY = 'pending_google_signup';

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['openid', 'profile', 'email'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        UtilisateurRepository $userRepository,
        EntityManagerInterface $em,
        Security $security,
    ): Response {
        try {
            $client      = $clientRegistry->getClient('google');
            $accessToken = $client->getAccessToken();

            /** @var GoogleUser $googleUser */
            $googleUser = $client->fetchUserFromToken($accessToken);

        } catch (\Throwable $e) {
            // Affiche le vrai message d'erreur pour le débogage
            $this->addFlash('error', '❌ Erreur Google OAuth : ' . $e->getMessage());

            return $this->redirectToRoute('app_login');
        }

        $googleId = trim((string) $googleUser->getId());
        $email    = trim((string) $googleUser->getEmail());

        if ($googleId === '' || $email === '') {
            $this->addFlash('error', '❌ Google n\'a pas fourni les informations nécessaires pour vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        $existingGoogleUser = $userRepository->findOneBy(['googleId' => $googleId]);
        if ($existingGoogleUser instanceof Utilisateur) {
            if (!$existingGoogleUser->isActivated()) {
                $this->addFlash('error', '❌ Votre compte est en attente d\'activation par un administrateur.');
                return $this->redirectToRoute('app_login');
            }
            return $security->login($existingGoogleUser, 'form_login', 'main');
        }

        $existingEmailUser = $userRepository->findOneBy(['email' => $email]);
        if ($existingEmailUser instanceof Utilisateur) {
            if ($existingEmailUser->getGoogleId() !== null && $existingEmailUser->getGoogleId() !== $googleId) {
                $this->addFlash('error', '❌ Cet email est déjà lié à un autre compte Google.');
                return $this->redirectToRoute('app_login');
            }

            $existingEmailUser->setGoogleId($googleId);
            $em->flush();

            if (!$existingEmailUser->isActivated()) {
                $this->addFlash('error', '❌ Votre compte est en attente d\'activation par un administrateur.');
                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('success', '✅ Votre compte Google a bien été lié à FarmVision.');
            return $security->login($existingEmailUser, 'form_login', 'main');
        }

        [$prenom, $nom] = $this->extractNames(
            (string) $googleUser->getName(),
            (string) $googleUser->getFirstName(),
            (string) $googleUser->getLastName(),
        );

        $request->getSession()->set(self::SESSION_KEY, [
            'google_id' => $googleId,
            'email'     => $email,
            'prenom'    => $prenom,
            'nom'       => $nom,
        ]);

        return $this->redirectToRoute('app_google_complete_profile');
    }

    #[Route('/complete-google-signup', name: 'app_google_complete_profile', methods: ['GET', 'POST'])]
    public function completeProfile(
        Request $request,
        UtilisateurRepository $userRepository,
        EntityManagerInterface $em,
        Security $security,
    ): Response {
        $pendingProfile = $request->getSession()->get(self::SESSION_KEY);

        if (!is_array($pendingProfile)) {
            return $this->redirectToRoute('app_login');
        }

        $errors  = [];
        $oldData = [
            'role'      => $request->request->get('role', 'AGRICULTEUR'),
            'telephone' => trim($request->request->get('telephone', '')),
            'adresse'   => trim($request->request->get('adresse', '')),
            'matricule' => trim($request->request->get('matricule', '')),
        ];

        if ($request->isMethod('POST')) {
            $role      = $oldData['role'];
            $telephone = $oldData['telephone'];
            $adresse   = $oldData['adresse'];
            $matricule = $oldData['matricule'];

            if (!in_array($role, ['AGRICULTEUR', 'RESPONSABLE_EXPLOITATION'], true)) {
                $errors['role'] = '❌ Le rôle sélectionné n\'est pas valide.';
            }

            if ($role === 'AGRICULTEUR' && $telephone !== '' && !preg_match('/^[0-9]{8}$/', $telephone)) {
                $errors['telephone'] = '❌ Le téléphone doit contenir exactement 8 chiffres.';
            }

            if ($adresse !== '' && strlen($adresse) > 255) {
                $errors['adresse'] = '❌ L\'adresse ne peut pas dépasser 255 caractères.';
            }

            if ($role === 'RESPONSABLE_EXPLOITATION') {
                if ($matricule === '') {
                    $errors['matricule'] = '❌ Le matricule est obligatoire pour un responsable d\'exploitation.';
                } elseif (!preg_match('/^[A-Z]{2,3}-[0-9]{3}$/', $matricule)) {
                    $errors['matricule'] = '❌ Format matricule invalide. Exemple: RES-001';
                }
            }

            if ($errors === []) {
                $existingUser = $userRepository->findOneBy(['email' => $pendingProfile['email']]);

                if ($existingUser instanceof Utilisateur) {
                    if ($existingUser->getGoogleId() !== null && $existingUser->getGoogleId() !== $pendingProfile['google_id']) {
                        $request->getSession()->remove(self::SESSION_KEY);
                        $this->addFlash('error', '❌ Cet email est déjà lié à un autre compte Google.');
                        return $this->redirectToRoute('app_login');
                    }

                    $existingUser->setGoogleId($pendingProfile['google_id']);
                    $em->flush();
                    $request->getSession()->remove(self::SESSION_KEY);

                    if (!$existingUser->isActivated()) {
                        $this->addFlash('error', '❌ Votre compte est en attente d\'activation par un administrateur.');
                        return $this->redirectToRoute('app_login');
                    }

                    return $security->login($existingUser, 'form_login', 'main');
                }

                $user = new Utilisateur();
                $user->setNom($pendingProfile['nom']);
                $user->setPrenom($pendingProfile['prenom']);
                $user->setEmail($pendingProfile['email']);
                $user->setGoogleId($pendingProfile['google_id']);
                $user->setTypeRole($role);
                $user->setActivated(1);

                if ($role === 'AGRICULTEUR') {
                    $user->setTelephone($telephone !== '' ? $telephone : null);
                    $user->setAdresse($adresse   !== '' ? $adresse   : null);
                }

                if ($role === 'RESPONSABLE_EXPLOITATION') {
                    $user->setMatricule($matricule);
                }

                $em->persist($user);
                $em->flush();
                $request->getSession()->remove(self::SESSION_KEY);

                $this->addFlash('success', '✅ Votre compte Google a été créé avec succès.');
                return $security->login($user, 'form_login', 'main');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('security/google_complete_profile.html.twig', [
            'googleProfile' => $pendingProfile,
            'errors'        => $errors,
            'old'           => $oldData,
        ]);
    }

    private function extractNames(string $fullName, string $firstName, string $lastName): array
    {
        $prenom = trim($firstName);
        $nom    = trim($lastName);

        if ($prenom === '' && $nom === '' && $fullName !== '') {
            $parts  = preg_split('/\s+/', trim($fullName)) ?: [];
            $prenom = array_shift($parts) ?? 'Utilisateur';
            $nom    = trim(implode(' ', $parts));
        }

        return [
            $prenom !== '' ? $prenom : 'Utilisateur',
            $nom    !== '' ? $nom    : 'Google',
        ];
    }
}