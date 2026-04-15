<?php
namespace App\CultureParcelle\Controller;

use App\Entity\Culture;
use App\CultureParcelle\Repository\CultureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/cultures')]
class CultureController extends AbstractController
{
   /* #[Route('/', name: 'front_culture_index', methods: ['GET'])]
    public function index(Request $request, CultureRepository $repo): Response
    {
        $search = $request->query->get('search', '');
        $type   = $request->query->get('type', 'all');

        return $this->render('@CultureParcelle/culture/index.html.twig', [
            'cultures'     => $repo->search($search ?: null, $type),
            'types'        => $repo->findAllTypes(),
            'search'       => $search,
            'selectedType' => $type,
        ]);
    }*/
        #[Route('/', name: 'front_culture_index', methods: ['GET'])]
public function index(Request $request, CultureRepository $repo): Response
{
    $search = $request->query->get('search', '');
    $type   = $request->query->get('type', 'all');

    // Get the currently logged-in user
    $user = $this->getUser();

    return $this->render('@CultureParcelle/culture/index.html.twig', [
        'cultures' => $repo->search(
            $search ?: null,
            $type,
            $user ? $user->getId() : null // filter by user ID
        ),
        'types'        => $repo->findAllTypes(),
        'search'       => $search,
        'selectedType' => $type,
    ]);
}

    #[Route('/new', name: 'front_culture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $culture = new Culture();
        $errors  = [];

        if ($request->isMethod('POST')) {
            $dateSemis   = $request->request->get('dateSemis', '');
            $dateRecolte = $request->request->get('dateRecolte', '');

            $culture->setNomCulture(trim($request->request->get('nomCulture', '')));
            $culture->setTypeCulture(trim($request->request->get('typeCulture', '')));
            $culture->setDateSemis($dateSemis ? new \DateTime($dateSemis) : null);
            $culture->setDateRecolte($dateRecolte ? new \DateTime($dateRecolte) : null);
            $culture->setUserId($this->getUser()?->getId());

            $violations = $validator->validate($culture);
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }

            if (empty($errors)) {
                $em->persist($culture);
                $em->flush();
                $this->addFlash('success', 'Culture ajoutée avec succès !');
                return $this->redirectToRoute('front_culture_index');
            }
        }

        return $this->render('@CultureParcelle/culture/new.html.twig', [
            'errors'  => $errors,
            'culture' => $culture,
        ]);
    }

    #[Route('/{idCulture}/edit', name: 'front_culture_edit', methods: ['GET', 'POST'])]
    public function edit(int $idCulture, Request $request, CultureRepository $repo, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $culture = $repo->find($idCulture);
        if (!$culture) throw $this->createNotFoundException('Culture non trouvée');

        $errors = [];

        if ($request->isMethod('POST')) {
            $dateSemis   = $request->request->get('dateSemis', '');
            $dateRecolte = $request->request->get('dateRecolte', '');

            $culture->setNomCulture(trim($request->request->get('nomCulture', '')));
            $culture->setTypeCulture(trim($request->request->get('typeCulture', '')));
            $culture->setDateSemis($dateSemis ? new \DateTime($dateSemis) : null);
            $culture->setDateRecolte($dateRecolte ? new \DateTime($dateRecolte) : null);

            $violations = $validator->validate($culture);
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }

            if (empty($errors)) {
                $em->flush();
                $this->addFlash('success', 'Culture modifiée avec succès !');
                return $this->redirectToRoute('front_culture_index');
            }
        }

        return $this->render('@CultureParcelle/culture/edit.html.twig', [
            'culture' => $culture,
            'errors'  => $errors,
        ]);
    }

    #[Route('/{idCulture}/delete', name: 'front_culture_delete', methods: ['POST'])]
    public function delete(int $idCulture, Request $request, CultureRepository $repo, EntityManagerInterface $em): Response
    {
        $culture = $repo->find($idCulture);
        if (!$culture) throw $this->createNotFoundException('Culture non trouvée');

        if ($this->isCsrfTokenValid('delete_culture_' . $idCulture, $request->request->get('_token'))) {
            $em->remove($culture);
            $em->flush();
            $this->addFlash('success', 'Culture "' . $culture->getNomCulture() . '" supprimée avec succès !');
        }

        return $this->redirectToRoute('front_culture_index');
    }
}
