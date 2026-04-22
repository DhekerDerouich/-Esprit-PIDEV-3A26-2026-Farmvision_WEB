<?php

namespace App\Controller\Admin;

use App\Entity\Revenu;
use App\Repository\RevenuRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/revenus')]
#[IsGranted('ROLE_ADMIN')]
class RevenuController extends AbstractController
{
    #[Route('/', name: 'admin_revenu_index')]
    public function index(
        Request $request,
        RevenuRepository $revenuRepository,
        UtilisateurRepository $utilisateurRepository,
        PaginatorInterface $paginator
    ): Response {
        $search        = $request->query->get('search', '');
        $responsableId = $request->query->get('responsable', 'all');
        $source        = $request->query->get('source', 'all');
        $startDate     = $request->query->get('start_date', '');
        $endDate       = $request->query->get('end_date', '');

        // NO addSelect('u') — returns only Revenu objects
        $queryBuilder = $revenuRepository->createQueryBuilder('r')
            ->leftJoin('App\Entity\Utilisateur', 'u', 'WITH', 'r.userId = u.id');

        if (!empty($search)) {
            $queryBuilder->andWhere(
                'u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search
                 OR r.source LIKE :search OR r.description LIKE :search'
            )->setParameter('search', '%' . $search . '%');
        }

        if ($responsableId !== 'all') {
            $queryBuilder->andWhere('r.userId = :responsableId')
                ->setParameter('responsableId', $responsableId);
        }

        if ($source !== 'all') {
            $queryBuilder->andWhere('r.source = :source')
                ->setParameter('source', $source);
        }

        if (!empty($startDate)) {
            $queryBuilder->andWhere('r.dateRevenu >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }

        if (!empty($endDate)) {
            $queryBuilder->andWhere('r.dateRevenu <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }

        $queryBuilder->orderBy('r.dateRevenu', 'DESC');

        $revenus = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        // Build map: userId => user object
        $allUsers = $utilisateurRepository->findAll();
        $responsablesMap = [];
        foreach ($allUsers as $user) {
            $responsablesMap[$user->getId()] = $user;
        }

        $sourceOptions = $revenuRepository->createQueryBuilder('r')
            ->select('DISTINCT r.source')
            ->getQuery()
            ->getResult();
        $sourceOptions = array_column($sourceOptions, 'source');

        $totalRevenus = $revenuRepository->createQueryBuilder('r')
            ->select('SUM(r.montant)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('admin/revenu/index.html.twig', [
            'revenus'             => $revenus,
            'responsables'        => $allUsers,
            'responsablesMap'     => $responsablesMap,
            'sourceOptions'       => $sourceOptions,
            'search'              => $search,
            'selectedResponsable' => $responsableId,
            'selectedSource'      => $source,
            'startDate'           => $startDate,
            'endDate'             => $endDate,
            'totalRevenus'        => $totalRevenus,
        ]);
    }

    #[Route('/{id}', name: 'admin_revenu_show')]
    public function show(int $id, RevenuRepository $revenuRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $revenu = $revenuRepository->find($id);

        if (!$revenu) {
            throw $this->createNotFoundException('Revenu non trouvé');
        }

        $responsable = null;
        if ($revenu->getUserId()) {
            $responsable = $utilisateurRepository->find($revenu->getUserId());
        }

        return $this->render('admin/revenu/show.html.twig', [
            'revenu'      => $revenu,
            'responsable' => $responsable,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_revenu_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, RevenuRepository $revenuRepository, EntityManagerInterface $em): Response
    {
        $revenu = $revenuRepository->find($id);

        if (!$revenu) {
            throw $this->createNotFoundException('Revenu non trouvé');
        }

        if ($this->isCsrfTokenValid('delete' . $revenu->getIdRevenu(), $request->request->get('_token'))) {
            $em->remove($revenu);
            $em->flush();
            $this->addFlash('success', 'Revenu supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_revenu_index');
    }
}