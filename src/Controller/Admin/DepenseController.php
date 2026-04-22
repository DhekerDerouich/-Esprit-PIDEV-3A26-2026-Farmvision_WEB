<?php

namespace App\Controller\Admin;

use App\Repository\DepenseRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/depenses')]
#[IsGranted('ROLE_ADMIN')]
class DepenseController extends AbstractController
{
    #[Route('/', name: 'admin_depense_index')]
    public function index(
        Request $request,
        DepenseRepository $depenseRepository,
        UtilisateurRepository $utilisateurRepository,
        PaginatorInterface $paginator
    ): Response {
        $search       = $request->query->get('search', '');
        $responsableId = $request->query->get('responsable', 'all');
        $type         = $request->query->get('type', 'all');
        $startDate    = $request->query->get('start_date', '');
        $endDate      = $request->query->get('end_date', '');

        $queryBuilder = $depenseRepository->createQueryBuilder('d')
            ->leftJoin('App\Entity\Utilisateur', 'u', 'WITH', 'd.userId = u.id');

        if (!empty($search)) {
            $queryBuilder->andWhere(
                'u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search
                 OR d.typeDepense LIKE :search OR d.description LIKE :search'
            )->setParameter('search', '%' . $search . '%');
        }

        if ($responsableId !== 'all') {
            $queryBuilder->andWhere('d.userId = :responsableId')
                ->setParameter('responsableId', $responsableId);
        }

        if ($type !== 'all') {
            $queryBuilder->andWhere('d.typeDepense = :type')
                ->setParameter('type', $type);
        }

        if (!empty($startDate)) {
            $queryBuilder->andWhere('d.dateDepense >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }

        if (!empty($endDate)) {
            $queryBuilder->andWhere('d.dateDepense <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }

        $queryBuilder->orderBy('d.dateDepense', 'DESC');

        $depenses = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        // Fetch all users and build a map: id => user
        $allUsers = $utilisateurRepository->findAll();
        $responsablesMap = [];
        foreach ($allUsers as $user) {
            $responsablesMap[$user->getId()] = $user;
        }

        $typeOptions = $depenseRepository->createQueryBuilder('d')
            ->select('DISTINCT d.typeDepense')
            ->getQuery()
            ->getResult();
        $typeOptions = array_column($typeOptions, 'typeDepense');

        $totalDepenses = $depenseRepository->createQueryBuilder('d')
            ->select('SUM(d.montant)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('admin/depense/index.html.twig', [
            'depenses'            => $depenses,
            'responsables'        => $allUsers,
            'responsablesMap'     => $responsablesMap,
            'typeOptions'         => $typeOptions,
            'search'              => $search,
            'selectedResponsable' => $responsableId,
            'selectedType'        => $type,
            'startDate'           => $startDate,
            'endDate'             => $endDate,
            'totalDepenses'       => $totalDepenses,
        ]);
    }

    #[Route('/{id}', name: 'admin_depense_show')]
    public function show(int $id, DepenseRepository $depenseRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $depense = $depenseRepository->find($id);

        if (!$depense) {
            throw $this->createNotFoundException('Dépense non trouvée');
        }

        $responsable = null;
        if ($depense->getUserId()) {
            $responsable = $utilisateurRepository->find($depense->getUserId());
        }

        return $this->render('admin/depense/show.html.twig', [
            'depense'     => $depense,
            'responsable' => $responsable,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_depense_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, DepenseRepository $depenseRepository, EntityManagerInterface $em): Response
    {
        $depense = $depenseRepository->find($id);

        if (!$depense) {
            throw $this->createNotFoundException('Dépense non trouvée');
        }

        if ($this->isCsrfTokenValid('delete' . $depense->getId(), $request->request->get('_token'))) {
            $em->remove($depense);
            $em->flush();
            $this->addFlash('success', 'Dépense supprimée avec succès !');
        }

        return $this->redirectToRoute('admin_depense_index');
    }
}