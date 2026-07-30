<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;
use App\Support\Application\Command\CreateRmaRecord\CreateRmaRecordCommand;
use App\Support\Application\Command\CreateRmaRecord\CreateRmaRecordHandler;
use App\Support\Application\Query\ListRmaRecords\ListRmaRecordsHandler;
use App\Support\Application\Query\ListRmaRecords\ListRmaRecordsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/support/rma')]
final class ApiSupportRmaController extends AbstractController
{
    #[IsGranted('ticket.view')]
    #[Route('', name: 'api_support_rma_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListRmaRecordsHandler $handler,
    ): JsonResponse {
        $ticketId = $request->query->get('ticketId');

        $records = $handler(new ListRmaRecordsQuery(
            ticketId: $ticketId ?: null,
        ));

        return $this->json(['data' => $records]);
    }

    #[IsGranted('ticket.create')]
    #[Route('', name: 'api_support_rma_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateRmaRecordHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $id = $handler(new CreateRmaRecordCommand(
                ticketId:     trim((string) ($payload['ticketId'] ?? '')),
                productCode:  trim((string) ($payload['productCode'] ?? '')),
                createdBy:    (string) $user->getDomainUser()->id(),
                serialNumber: isset($payload['serialNumber']) ? trim((string) $payload['serialNumber']) : null,
                quantity:     max(1, (int) ($payload['quantity'] ?? 1)),
                notes:        isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(['data' => ['id' => $id]], Response::HTTP_CREATED);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
