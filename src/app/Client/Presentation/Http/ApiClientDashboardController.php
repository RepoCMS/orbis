<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Query\GetClientDashboardSummary\GetClientDashboardSummaryHandler;
use App\Client\Application\Query\GetClientDashboardSummary\GetClientDashboardSummaryQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/client/dashboard')]
final class ApiClientDashboardController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('/summary', name: 'api_client_dashboard_summary', methods: ['GET'])]
    public function summary(GetClientDashboardSummaryHandler $handler): JsonResponse
    {
        $summary = ($handler)(new GetClientDashboardSummaryQuery());

        return $this->json(['data' => [
            'totalClients'          => $summary->totalClients,
            'activeContacts'        => $summary->activeContacts,
            'totalTerminals'        => $summary->totalTerminals,
            'terminalsByStatus'     => $summary->terminalsByStatus,
            'recentClients'         => $summary->recentClients,
            'clientsByStatus'       => $summary->clientsByStatus,
            'contractsExpiring'     => $summary->contractsExpiring,
            'topClientsByTerminals' => $summary->topClientsByTerminals,
            'contactsByStatus'      => $summary->contactsByStatus,
            'unassignedTerminals'   => $summary->unassignedTerminals,
            'monthlyGrowth'         => $summary->monthlyGrowth,
        ]]);
    }
}
