<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Query\GetClientDashboardSummary\GetClientDashboardSummaryHandler;
use App\Client\Application\Query\GetClientDashboardSummary\GetClientDashboardSummaryQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/dashboard', name: 'client_dashboard')]
final class ClientDashboardController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('', name: '', methods: ['GET'])]
    public function index(GetClientDashboardSummaryHandler $handler): Response
    {
        $summary = ($handler)(new GetClientDashboardSummaryQuery());

        $totalTerminals = $summary->totalTerminals;

        // Calcular el máximo para las barras horizontales
        $maxBreakdown = 1;
        foreach ($summary->terminalsByStatus as $item) {
            if ($item['count'] > $maxBreakdown) {
                $maxBreakdown = $item['count'];
            }
        }

        $maxClientsBreakdown = 1;
        foreach ($summary->clientsByStatus as $item) {
            if ($item['count'] > $maxClientsBreakdown) {
                $maxClientsBreakdown = $item['count'];
            }
        }

        $kpiCards = [
            [
                'label' => 'Total clientes',
                'value' => $summary->totalClients,
                'color' => '#1e3585',
                'icon'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            ],
            [
                'label' => 'Contactos activos',
                'value' => $summary->activeContacts,
                'color' => '#059669',
                'icon'  => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            ],
            [
                'label' => 'Terminales totales',
                'value' => $totalTerminals,
                'color' => '#0369a1',
                'icon'  => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25',
            ],
            [
                'label' => 'Terminales sin asignación',
                'value' => $summary->unassignedTerminals,
                'color' => '#d97706',
                'icon'  => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
            ],
        ];

        return $this->render('client/dashboard/index.html.twig', [
            'kpiCards'           => $kpiCards,
            'summary'            => $summary,
            'terminalsByStatus'   => $summary->terminalsByStatus,
            'maxBreakdown'       => $maxBreakdown,
            'clientsByStatus'    => $summary->clientsByStatus,
            'maxClientsBreakdown' => $maxClientsBreakdown,
            'recentClients'      => $summary->recentClients,
            'contractsExpiring'  => $summary->contractsExpiring,
            'topClientsByTerminals' => $summary->topClientsByTerminals,
            'contactsByStatus'   => $summary->contactsByStatus,
            'monthlyGrowth'      => $summary->monthlyGrowth,
        ]);
    }
}
