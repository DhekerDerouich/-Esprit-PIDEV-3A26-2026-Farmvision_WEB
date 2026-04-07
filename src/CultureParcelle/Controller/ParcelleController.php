<?php
namespace App\CultureParcelle\Controller;

use App\Entity\Parcelle;
use App\CultureParcelle\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/parcelles')]
class ParcelleController extends AbstractController
{
    #[Route('/', name: 'front_parcelle_index', methods: ['GET'])]
    public function index(Request $request, ParcelleRepository $repo): Response
    {
        $localisation  = $request->query->get('search', '');
        $surfaceMinRaw = $request->query->get('surface_min', '');
        $surfaceMaxRaw = $request->query->get('surface_max', '');

        return $this->render('@CultureParcelle/parcelle/index.html.twig', [
            'parcelles'  => $repo->search($localisation ?: null, $surfaceMinRaw !== '' ? (float)$surfaceMinRaw : null, $surfaceMaxRaw !== '' ? (float)$surfaceMaxRaw : null),
            'search'     => $localisation,
            'surfaceMin' => $surfaceMinRaw,
            'surfaceMax' => $surfaceMaxRaw,
        ]);
    }

    #[Route('/new', name: 'front_parcelle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $parcelle = new Parcelle();
        $errors   = [];

        if ($request->isMethod('POST')) {
            $surface = $request->request->get('surface', '');

            $parcelle->setLocalisation(trim($request->request->get('localisation', '')));
            $parcelle->setSurface($surface !== '' ? (float)$surface : null);
            $parcelle->setLatitude($request->request->get('latitude') !== '' ? (float)$request->request->get('latitude') : null);
            $parcelle->setLongitude($request->request->get('longitude') !== '' ? (float)$request->request->get('longitude') : null);
            $parcelle->setUserId($this->getUser()?->getId());

            $violations = $validator->validate($parcelle);
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }

            if (empty($errors)) {
                $em->persist($parcelle);
                $em->flush();
                $this->addFlash('success', 'Parcelle ajoutée avec succès !');
                return $this->redirectToRoute('front_parcelle_index');
            }
        }

        return $this->render('@CultureParcelle/parcelle/new.html.twig', [
            'errors'   => $errors,
            'parcelle' => $parcelle,
        ]);
    }

    #[Route('/{idParcelle}/edit', name: 'front_parcelle_edit', methods: ['GET', 'POST'])]
    public function edit(int $idParcelle, Request $request, ParcelleRepository $repo, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $parcelle = $repo->find($idParcelle);
        if (!$parcelle) throw $this->createNotFoundException('Parcelle non trouvée');

        $errors = [];

        if ($request->isMethod('POST')) {
            $surface = $request->request->get('surface', '');

            $parcelle->setLocalisation(trim($request->request->get('localisation', '')));
            $parcelle->setSurface($surface !== '' ? (float)$surface : null);
            $parcelle->setLatitude($request->request->get('latitude') !== '' ? (float)$request->request->get('latitude') : null);
            $parcelle->setLongitude($request->request->get('longitude') !== '' ? (float)$request->request->get('longitude') : null);

            $violations = $validator->validate($parcelle);
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }

            if (empty($errors)) {
                $em->flush();
                $this->addFlash('success', 'Parcelle modifiée avec succès !');
                return $this->redirectToRoute('front_parcelle_index');
            }
        }

        return $this->render('@CultureParcelle/parcelle/edit.html.twig', [
            'parcelle' => $parcelle,
            'errors'   => $errors,
        ]);
    }

    #[Route('/{idParcelle}/delete', name: 'front_parcelle_delete', methods: ['POST'])]
    public function delete(int $idParcelle, Request $request, ParcelleRepository $repo, EntityManagerInterface $em): Response
    {
        $parcelle = $repo->find($idParcelle);
        if (!$parcelle) throw $this->createNotFoundException('Parcelle non trouvée');

        if ($this->isCsrfTokenValid('delete_parcelle_' . $idParcelle, $request->request->get('_token'))) {
            $em->remove($parcelle);
            $em->flush();
            $this->addFlash('success', 'Parcelle "' . $parcelle->getLocalisation() . '" supprimée avec succès !');
        }

        return $this->redirectToRoute('front_parcelle_index');
    }
}
