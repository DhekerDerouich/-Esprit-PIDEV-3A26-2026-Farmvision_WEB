<?php
// src/Controller/Admin/UtilisateurController.php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        
        // Récupérer les paramètres de recherche et filtre
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', 'all');
        $status = $request->query->get('status', 'all');
        
        // Construire la requête avec filtres
        $qb = $repository->createQueryBuilder('u');
        
        if (!empty($search)) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($role !== 'all') {
            $qb->andWhere('u.type_role = :role')
               ->setParameter('role', $role);
        }
        
        if ($status !== 'all') {
            $qb->andWhere('u.activated = :status')
               ->setParameter('status', $status === 'active' ? 1 : 0);
        }
        
        $users = $qb->orderBy('u.id', 'DESC')->getQuery()->getResult();
        
        // Statistiques
        $stats = [
            'total' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult(),
            'admins' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'ADMINISTRATEUR')->getQuery()->getSingleScalarResult(),
            'responsables' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'RESPONSABLE_EXPLOITATION')->getQuery()->getSingleScalarResult(),
            'agriculteurs' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'AGRICULTEUR')->getQuery()->getSingleScalarResult(),
            'actifs' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.activated = 1')->getQuery()->getSingleScalarResult(),
            'inactifs' => $repository->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.activated = 0')->getQuery()->getSingleScalarResult(),
        ];
        
        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'search' => $search,
            'selectedRole' => $role,
            'selectedStatus' => $status,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setEmail($request->request->get('email'));
            $user->setTelephone($request->request->get('telephone'));
            $user->setAdresse($request->request->get('adresse'));
            $user->setTypeRole($request->request->get('type_role'));
            $user->setActivated($request->request->get('activated') ? 1 : 0);
            
            $em->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('admin_user_index');
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
    
    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $user = new Utilisateur();
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setEmail($request->request->get('email'));
            $user->setTelephone($request->request->get('telephone'));
            $user->setAdresse($request->request->get('adresse'));
            $user->setTypeRole($request->request->get('type_role'));
            $user->setActivated($request->request->get('activated') ? 1 : 0);
            
            $password = $request->request->get('password');
            if (!empty($password)) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }
            
            $em->persist($user);
            $em->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès');
            return $this->redirectToRoute('admin_user_index');
        }
        
        return $this->render('admin/user/new.html.twig');
    }
    #[Route('/ajax/search', name: 'admin_user_ajax_search', methods: ['GET'])]
public function ajaxSearch(Request $request, UtilisateurRepository $repository): Response
{
    $search = $request->query->get('q', '');
    $role = $request->query->get('role', 'all');
    
    $qb = $repository->createQueryBuilder('u');
    
    if (!empty($search)) {
        $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }
    
    if ($role !== 'all') {
        $qb->andWhere('u.type_role = :role')->setParameter('role', $role);
    }
    
    $users = $qb->getQuery()->getResult();
    
    $data = array_map(function($user) {
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'role' => $user->getTypeRole(),
            'activated' => $user->isActivated()
        ];
    }, $users);
    
    return $this->json($data);
}
}