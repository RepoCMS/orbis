<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Support\Application\Query\ListTickets\ListTicketsHandler;
use App\Support\Application\Query\ListTickets\ListTicketsQuery;
use App\Support\Domain\Enums\TicketStatus;
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
        ListTicketsHandler $handler,
    ): JsonResponse {
        $allTickets = $handler(new ListTicketsQuery(perPage: 1000));

        $openTicketsToday   = 0;
        $assignedTickets    = 0;
        $inProgressTickets  = 0;
        $pendingClosure     = 0;
        $overdueSla         = 0;

        $now = new \DateTimeImmutable();

        foreach ($allTickets as $ticket) {
            switch ($ticket->status) {
                case 'created':
                    ++$openTicketsToday;
                    break;
                case 'assigned':
                    ++$assignedTickets;
                    break;
                case 'in_transit_out':
                case 'waiting':
                case 'in_assistance':
                    ++$inProgressTickets;
                    break;
                case 'pending_closure':
                    ++$pendingClosure;
                    break;
            }

            if ($ticket->slaDeadlineAt !== null && $ticket->status !== 'closed' && $ticket->status !== 'cancelled') {
                $deadline = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $ticket->slaDeadlineAt);
                if ($deadline !== false && $now > $deadline) {
                    ++$overdueSla;
                }
            }
        }

        return $this->json([
            'data' => [
                'openTicketsToday'   => $openTicketsToday,
                'assignedTickets'    => $assignedTickets,
                'inProgressTickets'  => $inProgressTickets,
                'pendingClosure'     => $pendingClosure,
                'overdueSla'         => $overdueSla,
                'techniciansAvailable' => null,
                'techniciansBusy'      => null,
            ],
        ]);
    }
}
