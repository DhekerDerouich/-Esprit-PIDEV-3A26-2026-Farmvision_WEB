<?php
namespace App\CultureParcelle\Controller;

use App\CultureParcelle\Repository\CultureRepository;
use App\CultureParcelle\Service\CultureService;
use App\CultureParcelle\Service\CalendarService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cultures')]
class CultureController extends AbstractController
{
    public function __construct(
        private CultureService $cultureService,
        private CalendarService $calendarService,
        private CultureRepository $repository,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'front_culture_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $userId = $this->getUser()?->getId();

        $culturesQuery = $this->cultureService->getUserCultures($search ?: null, $type, $userId);
        
        $pagination = $this->paginator->paginate(
            $culturesQuery,
            $request->query->getInt('page', 1),
            10 // items per page
        );

        return $this->render('@CultureParcelle/culture/index.html.twig', [
            'cultures' => $pagination,
            'types' => $this->cultureService->getAllTypes(),
            'search' => $search,
            'selectedType' => $type,
        ]);
    }

    #[Route('/new', name: 'front_culture_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = [
                'nomCulture' => $request->request->get('nomCulture', ''),
                'typeCulture' => $request->request->get('typeCulture', ''),
                'dateSemis' => $request->request->get('dateSemis', ''),
                'dateRecolte' => $request->request->get('dateRecolte', ''),
            ];

            $result = $this->cultureService->createCulture($data, $this->getUser()?->getId());

            if ($result['success']) {
                $this->addFlash('success', 'Culture ajoutée avec succès !');
                return $this->redirectToRoute('front_culture_index');
            }

            return $this->render('@CultureParcelle/culture/new.html.twig', [
                'errors' => $result['errors'],
                'culture' => $result['culture'] ?? null,
            ]);
        }

        return $this->render('@CultureParcelle/culture/new.html.twig', [
            'errors' => [],
            'culture' => null,
        ]);
    }

    #[Route('/{idCulture}/edit', name: 'front_culture_edit', methods: ['GET', 'POST'])]
    public function edit(int $idCulture, Request $request): Response
    {
        $culture = $this->repository->find($idCulture);
        if (!$culture) {
            throw $this->createNotFoundException('Culture non trouvée');
        }

        if ($request->isMethod('POST')) {
            $data = [
                'nomCulture' => $request->request->get('nomCulture', ''),
                'typeCulture' => $request->request->get('typeCulture', ''),
                'dateSemis' => $request->request->get('dateSemis', ''),
                'dateRecolte' => $request->request->get('dateRecolte', ''),
            ];

            $result = $this->cultureService->updateCulture($culture, $data);

            if ($result['success']) {
                $this->addFlash('success', 'Culture modifiée avec succès !');
                return $this->redirectToRoute('front_culture_index');
            }

            return $this->render('@CultureParcelle/culture/edit.html.twig', [
                'culture' => $culture,
                'errors' => $result['errors'],
            ]);
        }

        return $this->render('@CultureParcelle/culture/edit.html.twig', [
            'culture' => $culture,
            'errors' => [],
        ]);
    }

    #[Route('/{idCulture}/delete', name: 'front_culture_delete', methods: ['POST'])]
    public function delete(int $idCulture, Request $request): Response
    {
        $culture = $this->repository->find($idCulture);
        if (!$culture) {
            throw $this->createNotFoundException('Culture non trouvée');
        }

        if ($this->isCsrfTokenValid('delete_culture_' . $idCulture, $request->request->get('_token'))) {
            $cultureName = $culture->getNomCulture();
            $this->cultureService->deleteCulture($culture);
            $this->addFlash('success', 'Culture "' . $cultureName . '" supprimée avec succès !');
        }

        return $this->redirectToRoute('front_culture_index');
    }

    #[Route('/calendar', name: 'front_culture_calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        return $this->render('@CultureParcelle/culture/calendar.html.twig');
    }

    #[Route('/calendar/events', name: 'front_culture_calendar_events', methods: ['GET'])]
    public function calendarEvents(): JsonResponse
    {
        $userId = $this->getUser()?->getId();
        $cultures = $this->cultureService->getUserCultures(null, 'all', $userId);
        $events = $this->calendarService->buildCalendarEvents($cultures);
        
        return $this->json($events);
    }

    #[Route('/calendar/quick-add', name: 'front_culture_calendar_quick_add', methods: ['POST'])]
    public function quickAdd(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $cultureData = [
            'nomCulture' => $data['nomCulture'] ?? '',
            'typeCulture' => $data['typeCulture'] ?? '',
            'dateSemis' => $data['date'] ?? '',
            'dateRecolte' => $data['dateRecolte'] ?? '',
        ];

        $result = $this->cultureService->createCulture($cultureData, $this->getUser()?->getId());

        if ($result['success']) {
            return $this->json(['success' => true, 'message' => 'Culture ajoutée avec succès']);
        }

        return $this->json(['success' => false, 'errors' => $result['errors']], 400);
    }

    #[Route('/calendar/update-date', name: 'front_culture_calendar_update_date', methods: ['POST'])]
    public function updateDate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $culture = $this->repository->find($data['cultureId']);
        if (!$culture) {
            return $this->json(['success' => false, 'message' => 'Culture non trouvée'], 404);
        }
        
        $newDate = new \DateTime($data['newDate']);
        $this->cultureService->updateCultureDate($culture, $data['type'], $newDate);
        
        return $this->json(['success' => true, 'message' => 'Date mise à jour avec succès']);
    }

    #[Route('/export/pdf', name: 'front_culture_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, \App\Service\PdfGeneratorService $pdfGenerator): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $userId = $this->getUser()?->getId();

        $cultures = $this->cultureService->getUserCultures($search ?: null, $type, $userId);

        $html = $this->renderView('@CultureParcelle/culture/pdf.html.twig', [
            'cultures' => $cultures,
            'date' => new \DateTime(),
            'user' => $this->getUser(),
            'search' => $search,
            'type' => $type
        ]);

        $filename = 'cultures_' . date('Y-m-d_His') . '.pdf';
        $result = $pdfGenerator->generatePdfResponse($html, $filename);

        return new Response(
            $result['content'],
            200,
            $result['headers']
        );
    }

    #[Route('/{idCulture}/export/pdf', name: 'front_culture_export_single_pdf', methods: ['GET'])]
    public function exportSinglePdf(int $idCulture, \App\Service\PdfGeneratorService $pdfGenerator): Response
    {
        $culture = $this->repository->find($idCulture);
        if (!$culture) {
            throw $this->createNotFoundException('Culture non trouvée');
        }

        $html = $this->renderView('@CultureParcelle/culture/pdf_single.html.twig', [
            'culture' => $culture,
            'date' => new \DateTime(),
            'user' => $this->getUser()
        ]);

        $filename = 'culture_' . $culture->getNomCulture() . '_' . date('Y-m-d') . '.pdf';
        $result = $pdfGenerator->generatePdfResponse($html, $filename);

        return new Response(
            $result['content'],
            200,
            $result['headers']
        );
    }
}
