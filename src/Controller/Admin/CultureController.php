<?php

namespace App\Controller\Admin;

use App\Entity\Culture;
use App\CultureParcelle\Repository\CultureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/cultures')]
class CultureController extends AbstractController
{
    #[Route('', name: 'admin_culture_index', methods: ['GET'])]
    public function index(Request $request, CultureRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $statut = $request->query->get('statut', '');
        
        $qb = $repository->createQueryBuilder('c');
        
        if ($search) {
            $qb->andWhere('c.nomCulture LIKE :search OR c.typeCulture LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($type) {
            $qb->andWhere('c.typeCulture = :type')
               ->setParameter('type', $type);
        }
        
        if ($statut === 'en_cours') {
            $qb->andWhere('c.dateRecolte IS NULL');
        } elseif ($statut === 'a_venir') {
            $qb->andWhere('c.dateRecolte IS NOT NULL AND c.dateRecolte > :today')
               ->setParameter('today', new \DateTime());
        } elseif ($statut === 'recoltee') {
            $qb->andWhere('c.dateRecolte IS NOT NULL AND c.dateRecolte <= :today')
               ->setParameter('today', new \DateTime());
        }
        
        $cultures = $qb->getQuery()->getResult();
        
        return $this->render('admin/culture/index.html.twig', [
            'cultures' => $cultures,
        ]);
    }

    #[Route('/new', name: 'admin_culture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $culture = new Culture();
        $errors = [];

        if ($request->isMethod('POST')) {
            $culture->setNomCulture($request->request->get('nomCulture'));
            $culture->setTypeCulture($request->request->get('typeCulture'));
            
            $dateSemis = $request->request->get('dateSemis');
            if ($dateSemis) {
                $culture->setDateSemis(new \DateTime($dateSemis));
            }
            
            $dateRecolte = $request->request->get('dateRecolte');
            if ($dateRecolte) {
                $culture->setDateRecolte(new \DateTime($dateRecolte));
            }
            
            $culture->setUserId($this->getUser()->getId());

            $violations = $validator->validate($culture);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
            } else {
                $em->persist($culture);
                $em->flush();
                
                $this->addFlash('success', 'Culture ajoutée avec succès');
                return $this->redirectToRoute('admin_culture_index');
            }
        }

        return $this->render('admin/culture/new.html.twig', [
            'culture' => $culture,
            'errors' => $errors,
        ]);
    }

    #[Route('/{idCulture}', name: 'admin_culture_show', methods: ['GET'])]
    public function show(Culture $culture): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/culture/show.html.twig', [
            'culture' => $culture,
        ]);
    }

    #[Route('/{idCulture}/edit', name: 'admin_culture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Culture $culture, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $errors = [];

        if ($request->isMethod('POST')) {
            $culture->setNomCulture($request->request->get('nomCulture'));
            $culture->setTypeCulture($request->request->get('typeCulture'));
            
            $dateSemis = $request->request->get('dateSemis');
            if ($dateSemis) {
                $culture->setDateSemis(new \DateTime($dateSemis));
            }
            
            $dateRecolte = $request->request->get('dateRecolte');
            if ($dateRecolte) {
                $culture->setDateRecolte(new \DateTime($dateRecolte));
            }

            $violations = $validator->validate($culture);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
            } else {
                $em->flush();
                
                $this->addFlash('success', 'Culture modifiée avec succès');
                return $this->redirectToRoute('admin_culture_index');
            }
        }

        return $this->render('admin/culture/edit.html.twig', [
            'culture' => $culture,
            'errors' => $errors,
        ]);
    }

    #[Route('/{idCulture}/delete', name: 'admin_culture_delete', methods: ['POST'])]
    public function delete(Culture $culture, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em->remove($culture);
        $em->flush();
        
        $this->addFlash('success', 'Culture supprimée avec succès');
        return $this->redirectToRoute('admin_culture_index');
    }
}
