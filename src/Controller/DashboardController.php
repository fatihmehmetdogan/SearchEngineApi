<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use App\Service\ScoringService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private ScoringService $scoringService
    ) {}

    #[Route('', name: 'dashboard_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $sort = $request->query->get('sort', 'score');
        $order = $request->query->get('order', 'desc');
        $type = $request->query->get('type');
        $limit = 10;

        $documents = $this->documentRepository->findForDashboard(
            page: $page,
            sort: $sort,
            order: $order,
            type: $type,
            limit: $limit
        );

        $totalDocuments = $this->documentRepository->countForDashboard($type);
        $totalPages = ceil($totalDocuments / $limit);

        return $this->render('dashboard/index.html.twig', [
            'documents' => $documents,
            'currentSort' => $sort,
            'currentOrder' => $order,
            'currentType' => $type,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
}