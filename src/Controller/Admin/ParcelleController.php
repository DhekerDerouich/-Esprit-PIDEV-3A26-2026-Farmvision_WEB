<?php

namespace App\Controller\Admin;

use App\Entity\Parcelle;
use App\CultureParcelle\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/parcelles')]
class ParcelleController extends AbstractController
{
    #[Route('', name: 'admin_parcelle_index', methods: ['GET'])]
    public function index(Request $request, ParcelleRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $search = $request->query->get('search', '');
        $surfaceMin = $request->query->get('surface_min', '');
        $surfaceMax = $request->query->get('surface_max', '');
        $geoloc = $request->query->get('geoloc', '');
        
        $qb = $repository->createQueryBuilder('p');
        
        if ($search) {
            $qb->andWhere('p.localisation LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($surfaceMin) {
            $qb->andWhere('p.surface >= :surfaceMin')
               ->setParameter('surfaceMin', (float) $surfaceMin);
        }
        
        if ($surfaceMax) {
            $qb->andWhere('p.surface <= :surfaceMax')
               ->setParameter('surfaceMax', (float) $surfaceMax);
        }
        
        if ($geoloc === 'oui') {
            $qb->andWhere('p.latitude IS NOT NULL AND p.longitude IS NOT NULL');
        } elseif ($geoloc === 'non') {
            $qb->andWhere('p.latitude IS NULL OR p.longitude IS NULL');
        }
        
        $parcelles = $qb->getQuery()->getResult();
        
        return $this->render('admin/parcelle/index.html.twig', [
            'parcelles' => $parcelles,
        ]);
    }

    #[Route('/new', name: 'admin_parcelle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $parcelle = new Parcelle();
        $errors = [];

        if ($request->isMethod('POST')) {
            $parcelle->setLocalisation($request->request->get('localisation'));
            $parcelle->setSurface((float) $request->request->get('surface'));
            
            $latitude = $request->request->get('latitude');
            if ($latitude) {
                $parcelle->setLatitude((float) $latitude);
            }
            
            $longitude = $request->request->get('longitude');
            if ($longitude) {
                $parcelle->setLongitude((float) $longitude);
            }
            
            $parcelle->setUserId($this->getUser()->getId());

            $violations = $validator->validate($parcelle);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
            } else {
                $em->persist($parcelle);
                $em->flush();
                
                $this->addFlash('success', 'Parcelle ajoutée avec succès');
                return $this->redirectToRoute('admin_parcelle_index');
            }
        }

        return $this->render('admin/parcelle/new.html.twig', [
            'parcelle' => $parcelle,
            'errors' => $errors,
        ]);
    }

    #[Route('/{idParcelle}', name: 'admin_parcelle_show', methods: ['GET'])]
    public function show(Parcelle $parcelle): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/parcelle/show.html.twig', [
            'parcelle' => $parcelle,
        ]);
    }

    #[Route('/{idParcelle}/edit', name: 'admin_parcelle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Parcelle $parcelle, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $errors = [];

        if ($request->isMethod('POST')) {
            $parcelle->setLocalisation($request->request->get('localisation'));
            $parcelle->setSurface((float) $request->request->get('surface'));
            
            $latitude = $request->request->get('latitude');
            if ($latitude) {
                $parcelle->setLatitude((float) $latitude);
            }
            
            $longitude = $request->request->get('longitude');
            if ($longitude) {
                $parcelle->setLongitude((float) $longitude);
            }

            $violations = $validator->validate($parcelle);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
            } else {
                $em->flush();
                
                $this->addFlash('success', 'Parcelle modifiée avec succès');
                return $this->redirectToRoute('admin_parcelle_index');
            }
        }

        return $this->render('admin/parcelle/edit.html.twig', [
            'parcelle' => $parcelle,
            'errors' => $errors,
        ]);
    }

    #[Route('/{idParcelle}/delete', name: 'admin_parcelle_delete', methods: ['POST'])]
    public function delete(Parcelle $parcelle, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em->remove($parcelle);
        $em->flush();
        
        $this->addFlash('success', 'Parcelle supprimée avec succès');
        return $this->redirectToRoute('admin_parcelle_index');
    }
}
