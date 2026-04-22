<?php

namespace App\Controller\Admin;

use App\Entity\RapportPersonnalise;
use App\Repository\RapportPersonnaliseRepository;
use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\RevenuRepository;
use App\Repository\DepenseRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/rapports')]
class RapportPersonnaliseController extends AbstractController
{
    #[Route('/', name: 'admin_rapport_index')]
    public function index(RapportPersonnaliseRepository $rapportRepo): Response
    {
        $rapports = $rapportRepo->getRecentReports(20);
        return $this->render('admin/rapport/index.html.twig', ['rapports' => $rapports]);
    }

    #[Route('/new', name: 'admin_rapport_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        EquipementRepository $equipementRepo,
        MaintenanceRepository $maintenanceRepo,
        RevenuRepository $revenuRepo,
        DepenseRepository $depenseRepo,
        UtilisateurRepository $utilisateurRepo
    ): Response {
        if ($request->isMethod('POST')) {
            $rapport = new RapportPersonnalise();
            $rapport->setTitre($request->request->get('titre'));
            $rapport->setType($request->request->get('type'));
            $rapport->setDescription($request->request->get('description'));
            $rapport->setCreatedBy($this->getUser());

            if ($request->request->get('date_debut')) {
                $rapport->setDateDebut(new \DateTime($request->request->get('date_debut')));
            }
            if ($request->request->get('date_fin')) {
                $rapport->setDateFin(new \DateTime($request->request->get('date_fin')));
            }

            $donnees = $this->generateReportData(
                $rapport->getType(),
                $rapport->getDateDebut(),
                $rapport->getDateFin(),
                $equipementRepo,
                $maintenanceRepo,
                $revenuRepo,
                $depenseRepo,
                $utilisateurRepo
            );
            $rapport->setDonnees($donnees);

            $em->persist($rapport);
            $em->flush();

            $this->addFlash('success', 'Rapport créé avec succès.');
            return $this->redirectToRoute('admin_rapport_show', ['id' => $rapport->getId()]);
        }

        return $this->render('admin/rapport/new.html.twig');
    }

    #[Route('/{id}', name: 'admin_rapport_show')]
    public function show(RapportPersonnalise $rapport): Response
    {
        return $this->render('admin/rapport/show.html.twig', ['rapport' => $rapport]);
    }

    #[Route('/{id}/delete', name: 'admin_rapport_delete', methods: ['POST'])]
    public function delete(RapportPersonnalise $rapport, EntityManagerInterface $em): Response
    {
        $em->remove($rapport);
        $em->flush();
        $this->addFlash('success', 'Rapport supprimé avec succès.');
        return $this->redirectToRoute('admin_rapport_index');
    }

    private function generateReportData(
        string $type,
        ?\DateTimeInterface $dateDebut,
        ?\DateTimeInterface $dateFin,
        EquipementRepository $equipementRepo,
        MaintenanceRepository $maintenanceRepo,
        RevenuRepository $revenuRepo,
        DepenseRepository $depenseRepo,
        UtilisateurRepository $utilisateurRepo
    ): array {
        $data = ['type' => $type, 'generated_at' => (new \DateTime())->format('Y-m-d H:i:s')];

        switch ($type) {
            case 'finance':
                $revenusStats = $revenuRepo->getStatistics();
                $depensesStats = $depenseRepo->getStatistics();
                $data['revenus'] = $revenusStats;
                $data['depenses'] = $depensesStats;
                $data['balance'] = ($revenusStats['total'] ?? 0) - ($depensesStats['total'] ?? 0);
                break;

            case 'equipement':
                $data['equipements'] = $equipementRepo->getStatistics();
                $data['liste'] = $equipementRepo->findAll();
                break;

            case 'maintenance':
                $data['maintenances'] = $maintenanceRepo->getStatistics();
                $data['upcoming'] = $maintenanceRepo->findUpcoming(10);
                break;

            case 'utilisateur':
                $qb = $utilisateurRepo->createQueryBuilder('u');
                $data['total'] = $qb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
                $data['admins'] = $utilisateurRepo->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'ADMINISTRATEUR')->getQuery()->getSingleScalarResult();
                $data['responsables'] = $utilisateurRepo->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'RESPONSABLE_EXPLOITATION')->getQuery()->getSingleScalarResult();
                $data['agriculteurs'] = $utilisateurRepo->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'AGRICULTEUR')->getQuery()->getSingleScalarResult();
                break;

            case 'global':
            default:
                $data['equipements'] = $equipementRepo->getStatistics();
                $data['maintenances'] = $maintenanceRepo->getStatistics();
                $data['revenus'] = $revenuRepo->getStatistics();
                $data['depenses'] = $depenseRepo->getStatistics();
                break;
        }

        return $data;
    }
}
