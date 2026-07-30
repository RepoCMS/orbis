<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Support\Application\Query\SupportDashboardMetrics\SupportDashboardMetricsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support/dashboard', name: 'support_dashboard')]
final class SupportDashboardController extends AbstractController
{
    #[IsGranted('ticket.view')]
    #[Route('', name: '', methods: ['GET'])]
    public function index(
        SupportDashboardMetricsHandler $handler,
    ): Response {
        $metrics = $handler();

        $serviceTypeLabels = [
            'preaslistamiento' => 'Prealistamiento',
            'instalacion'      => 'Instalaci&oacute;n',
            'desinstalacion'   => 'Desinstalaci&oacute;n',
            'visita'           => 'Visita',
            'activacion'       => 'Activaci&oacute;n',
            'desactivacion'    => 'Desactivaci&oacute;n',
            'correctivo'       => 'Correctivo',
            'mantenimiento'    => 'Mantenimiento',
        ];

        $statusLabels = [
            'created'          => 'Creado',
            'assigned'         => 'Asignado',
            'in_transit_out'   => 'En tr&aacute;nsito (ida)',
            'waiting'          => 'En espera',
            'in_assistance'    => 'En asistencia',
            'paused'           => 'Pausado',
            'in_transit_back'  => 'En tr&aacute;nsito (vuelta)',
            'pending_closure'  => 'Pend. cierre',
            'reopened'         => 'Reabierto',
            'closed'           => 'Cerrado',
            'cancelled'        => 'Cancelado',
        ];

        $statusColors = [
            'created'          => '#6b7280',
            'assigned'         => '#0369a1',
            'in_transit_out'   => '#6366f1',
            'waiting'          => '#d97706',
            'in_assistance'    => '#ea580c',
            'paused'           => '#dc2626',
            'in_transit_back'  => '#7c3aed',
            'pending_closure'  => '#6d28d9',
            'reopened'         => '#e11d48',
            'closed'           => '#16a34a',
            'cancelled'        => '#9ca3af',
        ];

        $kpiCards = [
            [
                'label' => 'Abiertos',
                'value' => $metrics->openTickets,
                'color' => '#6b7280',
                'icon'  => 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a3 3 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z',
            ],
            [
                'label' => 'Asignados',
                'value' => $metrics->assignedTickets,
                'color' => '#0369a1',
                'icon'  => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
            ],
            [
                'label' => 'En progreso',
                'value' => $metrics->inProgressTickets,
                'color' => '#d97706',
                'icon'  => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Pend. cierre',
                'value' => $metrics->pendingClosure,
                'color' => '#6d28d9',
                'icon'  => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'SLA vencido',
                'value' => $metrics->overdueSla,
                'color' => '#dc2626',
                'icon'  => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
            ],
            [
                'label' => 'Completados hoy',
                'value' => $metrics->completedToday,
                'color' => '#16a34a',
                'icon'  => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Completados semana',
                'value' => $metrics->completedThisWeek,
                'color' => '#2563eb',
                'icon'  => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
            ],
            [
                'label' => 'Brecha SLA',
                'value' => $metrics->slaBreachRate30d . '%',
                'color' => $metrics->slaBreachRate30d > 10 ? '#dc2626' : '#16a34a',
                'icon'  => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Tpo. resolución prom.',
                'value' => $metrics->avgResolutionHours . 'h',
                'color' => '#7c3aed',
                'icon'  => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Técnicos disp/ocup',
                'value' => $metrics->techniciansAvailable . '/' . $metrics->techniciansBusy,
                'color' => '#6366f1',
                'icon'  => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
            ],
            [
                'label' => 'Total tickets',
                'value' => $metrics->totalTickets,
                'color' => '#6b7280',
                'icon'  => 'M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z',
            ],
        ];

        $statusBreakdown = [];
        $maxCnt = 0;
        foreach ($metrics->statusBreakdown as $row) {
            $s = $row['status'];
            $cnt = (int) $row['cnt'];
            $statusBreakdown[] = [
                'status' => $s,
                'label'  => $statusLabels[$s] ?? $s,
                'count'  => $cnt,
                'color'  => $statusColors[$s] ?? '#6b7280',
            ];
            if ($cnt > $maxCnt) {
                $maxCnt = $cnt;
            }
        }

        return $this->render('support/dashboard/index.html.twig', [
            'kpiCards'              => $kpiCards,
            'statusBreakdown'       => $statusBreakdown,
            'maxBreakdown'          => $maxCnt ?: 1,
            'metrics'               => $metrics,
            'serviceTypeLabels'     => $serviceTypeLabels,
            'statusLabels'          => $statusLabels,
            'statusColors'          => $statusColors,
        ]);
    }
}
