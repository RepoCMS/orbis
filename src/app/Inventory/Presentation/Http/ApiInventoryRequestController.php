<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Inventory\Application\Command\AcceptByTechnician\AcceptByTechnicianCommand;
use App\Inventory\Application\Command\AcceptByTechnician\AcceptByTechnicianHandler;
use App\Inventory\Application\Command\ApproveRequest\ApproveRequestCommand;
use App\Inventory\Application\Command\ApproveRequest\ApproveRequestHandler;
use App\Inventory\Application\Command\CancelRequest\CancelRequestCommand;
use App\Inventory\Application\Command\CancelRequest\CancelRequestHandler;
use App\Inventory\Application\Command\FulfillRequestItem\FulfillRequestItemCommand;
use App\Inventory\Application\Command\FulfillRequestItem\FulfillRequestItemHandler;
use App\Inventory\Application\Command\RejectByTechnician\RejectByTechnicianCommand;
use App\Inventory\Application\Command\RejectByTechnician\RejectByTechnicianHandler;
use App\Inventory\Application\Command\RejectRequest\RejectRequestCommand;
use App\Inventory\Application\Command\RejectRequest\RejectRequestHandler;
use App\Inventory\Application\Command\SubmitRequest\SubmitRequestCommand;
use App\Inventory\Application\Command\SubmitRequest\SubmitRequestHandler;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Application\Query\FindRequestById\FindRequestByIdHandler;
use App\Inventory\Application\Query\ListRequests\ListRequestsHandler;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory/requests')]
final class ApiInventoryRequestController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'api_inventory_requests_list', methods: ['GET'])]
    public function listRequests(
        Request $request,
        ListRequestsHandler $handler,
    ): JsonResponse {
        $status     = $request->query->get('status');
        $requestedBy = $request->query->get('requestedBy');

        $requests = $handler($status);

        if ($requestedBy !== null) {
            $requests = array_values(array_filter(
                $requests,
                static fn ($r) => $r->requestedBy === $requestedBy,
            ));
        }

        return $this->json(['data' => $requests]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'api_inventory_requests_create', methods: ['POST'])]
    public function createRequest(
        Request $request,
        SubmitRequestHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        $user        = $this->getUser();
        $requestedBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            $rawItems = $payload['items'] ?? [];
            if (!is_array($rawItems) || $rawItems === []) {
                throw new \InvalidArgumentException('Debe proporcionar al menos un item en "items".');
            }

            $items = [];
            foreach ($rawItems as $item) {
                $items[] = new \App\Inventory\Application\ValueObjects\RequestItemCommandInput(
                    productId:          trim((string) ($item['productId'] ?? '')),
                    serializedStockId:  isset($item['serializedStockId']) ? trim((string) $item['serializedStockId']) : null,
                    quantity:           (int) ($item['quantity'] ?? 1),
                );
            }

            ($handler)(new SubmitRequestCommand(
                items:                $items,
                sourceLocationId:     trim((string) ($payload['sourceLocationId'] ?? '')),
                destinationLocationId: trim((string) ($payload['destinationLocationId'] ?? '')),
                requestedBy:          $requestedBy,
                notes:                isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(['data' => ['status' => 'created']], Response::HTTP_CREATED);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'api_inventory_requests_show', methods: ['GET'])]
    public function showRequest(
        string $id,
        FindRequestByIdHandler $handler,
    ): JsonResponse {
        try {
            $request = $handler($id);

            return $this->json(['data' => $request]);
        } catch (RequestNotFoundException) {
            return $this->json(['error' => 'Solicitud de stock no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/approve', name: 'api_inventory_requests_approve', methods: ['POST'])]
    public function approveRequest(
        string $id,
        ApproveRequestHandler $handler,
    ): JsonResponse {
        $user       = $this->getUser();
        $approvedBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new ApproveRequestCommand(
                requestId:  $id,
                approvedBy: $approvedBy,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'approved']]);
        } catch (RequestNotFoundException) {
            return $this->json(['error' => 'Solicitud de stock no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/reject', name: 'api_inventory_requests_reject', methods: ['POST'])]
    public function rejectRequest(
        string $id,
        Request $request,
        RejectRequestHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $user    = $this->getUser();
        $rejectedBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new RejectRequestCommand(
                requestId:   $id,
                rejectedBy:  $rejectedBy,
                reason:      trim((string) ($payload['reason'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'rejected']]);
        } catch (RequestNotFoundException) {
            return $this->json(['error' => 'Solicitud de stock no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/accept-by-technician', name: 'api_inventory_requests_accept_technician', methods: ['POST'])]
    public function acceptByTechnician(
        string $id,
        AcceptByTechnicianHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new AcceptByTechnicianCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'fulfilled']]);
        } catch (RequestNotFoundException) {
            return $this->json(['error' => 'Solicitud de stock no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/reject-by-technician', name: 'api_inventory_requests_reject_technician', methods: ['POST'])]
    public function rejectByTechnician(
        string $id,
        Request $request,
        RejectByTechnicianHandler $handler,
    ): JsonResponse {
        $payload    = $request->toArray();
        $user       = $this->getUser();
        $rejectedBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new RejectByTechnicianCommand(
                requestId:  $id,
                rejectedBy: $rejectedBy,
                reason:     trim((string) ($payload['reason'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'rejected']]);
        } catch (RequestNotFoundException) {
            return $this->json(['error' => 'Solicitud de stock no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.create')]
    #[Route('/{id}/cancel', name: 'api_inventory_requests_cancel', methods: ['POST'])]
    public function cancelRequest(
        string $id,
        CancelRequestHandler $handler,
    ): JsonResponse {
        $user        = $this->getUser();
        $cancelledBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new CancelRequestCommand(
                requestId:   $id,
                cancelledBy: $cancelledBy,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'cancelled']]);
        } catch (RequestNotFoundException) {
            return $this->json(['error' => 'Solicitud de stock no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/fulfill-item/{itemId}', name: 'api_inventory_requests_fulfill_item', methods: ['POST'])]
    public function fulfillItem(
        string $id,
        string $itemId,
        FulfillRequestItemHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new FulfillRequestItemCommand($id, $itemId));

            return $this->json(['data' => ['id' => $id, 'itemId' => $itemId, 'status' => 'fulfilled']]);
        } catch (RequestNotFoundException) {
            return $this->json(['error' => 'Solicitud de stock no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.manage')]
    #[Route('/batch-accept', name: 'api_inventory_requests_batch_accept', methods: ['POST'])]
    public function batchAccept(
        Request $request,
        AcceptByTechnicianHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $ids = $payload['ids'] ?? [];

        if (!is_array($ids) || $ids === []) {
            return $this->json(
                ['error' => 'Debe proporcionar un arreglo "ids" no vacío.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $results = [];
        $errors  = [];

        foreach ($ids as $requestId) {
            $id = trim((string) $requestId);

            try {
                ($handler)(new AcceptByTechnicianCommand($id));
                $results[] = ['id' => $id, 'status' => 'fulfilled'];
            } catch (RequestNotFoundException) {
                $errors[] = ['id' => $id, 'error' => 'Solicitud no encontrada.'];
            } catch (\Exception $e) {
                $errors[] = ['id' => $id, 'error' => $e->getMessage()];
            }
        }

        return $this->json([
            'data' => [
                'succeeded' => $results,
                'failed'    => $errors,
            ],
        ]);
    }

    #[IsGranted('inventory.manage')]
    #[Route('/batch-reject', name: 'api_inventory_requests_batch_reject', methods: ['POST'])]
    public function batchReject(
        Request $request,
        RejectByTechnicianHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $ids     = $payload['ids'] ?? [];
        $reason  = trim((string) ($payload['reason'] ?? 'Rechazo masivo por técnico.'));

        if (!is_array($ids) || $ids === []) {
            return $this->json(
                ['error' => 'Debe proporcionar un arreglo "ids" no vacío.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $results = [];
        $errors  = [];
        $user    = $this->getUser();
        $userId  = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        foreach ($ids as $requestId) {
            $id = trim((string) $requestId);

            try {
                ($handler)(new RejectByTechnicianCommand($id, $userId, $reason));
                $results[] = ['id' => $id, 'status' => 'rejected'];
            } catch (RequestNotFoundException) {
                $errors[] = ['id' => $id, 'error' => 'Solicitud no encontrada.'];
            } catch (\Exception $e) {
                $errors[] = ['id' => $id, 'error' => $e->getMessage()];
            }
        }

        return $this->json([
            'data' => [
                'succeeded' => $results,
                'failed'    => $errors,
            ],
        ]);
    }
}
