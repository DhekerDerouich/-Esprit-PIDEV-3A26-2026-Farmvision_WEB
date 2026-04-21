<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Utilisateur;
use App\Repository\ConversationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/messages')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'messages_index')]
    public function index(ConversationRepository $convRepo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $conversations = $convRepo->findByUser($user);

        return $this->render('messages/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/new', name: 'messages_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UtilisateurRepository $userRepo, ConversationRepository $convRepo): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $receiverId = $request->request->get('receiver_id');
            $content = trim($request->request->get('content', ''));

            if (!$receiverId) {
                $errors[] = 'Veuillez sélectionner un destinataire.';
            }
            if (empty($content)) {
                $errors[] = 'Le message ne peut pas être vide.';
            }

            if (empty($errors)) {
                $receiver = $userRepo->find($receiverId);
                if (!$receiver) {
                    $errors[] = 'Destinataire invalide.';
                } else {
                    $conv = $convRepo->findDirectConversation($currentUser, $receiver);

                    if (!$conv) {
                        $conv = new Conversation();
                        $conv->setType('direct');
                        $conv->addParticipant($currentUser);
                        $conv->addParticipant($receiver);
                        $em->persist($conv);
                    }

                    $msg = new Message();
                    $msg->setConversation($conv);
                    $msg->setSender($currentUser);
                    $msg->setContent($content);
                    $em->persist($msg);

                    $conv->setLastMessageAt(new \DateTime());
                    $em->flush();

                    return $this->redirectToRoute('messages_show', ['id' => $conv->getId()]);
                }
            }
        }

        $users = $userRepo->findAll();
        $contacts = array_filter($users, fn($u) => $u->getId() !== $currentUser->getId());

        return $this->render('messages/new.html.twig', [
            'errors' => $errors,
            'contacts' => $contacts,
        ]);
    }

    #[Route('/{id}', name: 'messages_show')]
    public function show(Conversation $conversation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $participants = $conversation->getParticipants();
        if (!$participants->contains($user)) {
            throw $this->createAccessDeniedException();
        }

        $messages = $em->getRepository(Message::class)->createQueryBuilder('m')
            ->andWhere('m.conversation = :conv')
            ->setParameter('conv', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $other = $conversation->getOtherParticipant($user);

        return $this->render('messages/show.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
            'other' => $other,
        ]);
    }

    #[Route('/{id}/send', name: 'messages_send', methods: ['POST'])]
    public function send(Request $request, Conversation $conversation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $participants = $conversation->getParticipants();
        if (!$participants->contains($user)) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('content', ''));
        if (!empty($content)) {
            $msg = new Message();
            $msg->setConversation($conversation);
            $msg->setSender($user);
            $msg->setContent($content);
            $em->persist($msg);

            $conversation->setLastMessageAt(new \DateTime());
            $em->flush();
        }

        return $this->redirectToRoute('messages_show', ['id' => $conversation->getId()]);
    }

    #[Route('/broadcast', name: 'messages_broadcast', methods: ['GET', 'POST'])]
    public function broadcast(Request $request, EntityManagerInterface $em, UtilisateurRepository $userRepo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur || $user->getTypeRole() !== 'ADMIN') {
            throw $this->createAccessDeniedException();
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $content = trim($request->request->get('content', ''));
            $targetRole = $request->request->get('target_role', 'ALL');

            if (empty($content)) {
                $errors[] = 'Le message ne peut pas être vide.';
            }

            if (empty($errors)) {
                $conv = new Conversation();
                $conv->setType('broadcast');
                $conv->setTitle('Broadcast: ' . substr($content, 0, 30) . '...');
                $conv->addParticipant($user);

                $targets = $targetRole === 'ALL' 
                    ? $userRepo->findAll()
                    : $userRepo->findBy(['typeRole' => $targetRole]);

                foreach ($targets as $target) {
                    if ($target->getId() !== $user->getId()) {
                        $conv->addParticipant($target);
                    }
                }

                $em->persist($conv);
                $em->flush();

                $msg = new Message();
                $msg->setConversation($conv);
                $msg->setSender($user);
                $msg->setContent($content);
                $em->persist($msg);
                $em->flush();

                $this->addFlash('success', 'Message broadcast envoyé à ' . count($targets) . ' utilisateur(s)');
                return $this->redirectToRoute('messages_index');
            }
        }

        return $this->render('messages/broadcast.html.twig', [
            'errors' => $errors,
        ]);
    }
}