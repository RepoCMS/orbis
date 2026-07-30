<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Support\Application\Query\SupportDashboardMetrics\SupportDashboardMetricsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/support')]
final class ApiSupportDashboardController extends AbstractController
{
    #[IsGranted('ticket.view')]
    #[Route('/dashboard/summary', name: 'api_support_dashboard_summary', methods: ['GET'])]
    public function summary(
        SupportDashboardMetricsHandler $handler,
    ): JsonResponse {
        $metrics = $handler();

        return $this->json([
            'data' => [
                'openTicketsToday'   => $metrics->openTickets,
                'assignedTickets'    => $metrics->assignedTickets,
                'inProgressTickets'  => $metrics->inProgressTickets,
                'pendingClosure'     => $metrics->pendingClosure,
                'overdueSla'         => $metrics->overdueSla,
                'completedToday'     => $metrics->completedToday,
                'completedThisWeek'  => $metrics->completedThisWeek,
                'avgResolutionHours' => $metrics->avgResolutionHours,
                'slaBreachCount30d'  => $metrics->slaBreachCount30d,
                'slaBreachRate30d'   => $metrics->slaBreachRate30d,
                'techniciansAvailable' => $metrics->techniciansAvailable,
                'techniciansBusy'      => $metrics->techniciansBusy,
                'totalTickets'        => $metrics->totalTickets,
            ],
        ]);
    }
}
