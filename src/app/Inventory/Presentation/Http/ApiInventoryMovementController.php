<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Inventory\Application\Command\CancelTransfer\CancelTransferCommand;
use App\Inventory\Application\Command\CancelTransfer\CancelTransferHandler;
use App\Inventory\Application\Command\ConfirmTransfer\ConfirmTransferCommand;
use App\Inventory\Application\Command\ConfirmTransfer\ConfirmTransferHandler;
use App\Inventory\Application\Command\CreateTransfer\CreateTransferCommand;
use App\Inventory\Application\Command\CreateTransfer\CreateTransferHandler;
use App\Inventory\Application\Command\RejectTransfer\RejectTransferCommand;
use App\Inventory\Application\Command\RejectTransfer\RejectTransferHandler;
use App\Inventory\Application\Query\FindMovementById\FindMovementByIdHandler;
use App\Inventory\Application\Query\ListMovements\ListMovementsHandler;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory')]
final class ApiInventoryMovementController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('/movements', name: 'api_inventory_movements_list', methods: ['GET'])]
    public function listMovements(
        Request $request,
        ListMovementsHandler $handler,
    ): JsonResponse {
        $movements = $handler();

        $productId             = $request->query->get('productId');
        $type                  = $request->query->get('type');
        $status                = $request->query->get('status');
        $sourceLocationId      = $request->query->get('sourceLocationId');
        $destinationLocationId = $request->query->get('destinationLocationId');
        $dateFrom              = $request->query->get('dateFrom');
        $dateTo                = $request->query->get('dateTo');

        if ($productId !== null) {
            $movements = array_values(array_filter(
                $movements,
                static fn($m) => $m->productId === $productId,
            ));
        }

        if ($type !== null) {
            $movements = array_values(array_filter(
                $movements,
                static fn($m) => $m->type === $type,
            ));
        }

        if ($status !== null) {
            $movements = array_values(array_filter(
                $movements,
                static fn($m) => $m->status === $status,
            ));
        }

        if ($sourceLocationId !== null) {
            $movements = array_values(array_filter(
                $movements,
                static fn($m) => $m->sourceLocationId === $sourceLocationId,
            ));
        }

        if ($destinationLocationId !== null) {
            $movements = array_values(array_filter(
                $movements,
                static fn($m) => $m->destinationLocationId === $destinationLocationId,
            ));
        }

        if ($dateFrom !== null) {
            $movements = array_values(array_filter(
                $movements,
                static fn($m) => $m->createdAt >= $dateFrom,
            ));
        }

        if ($dateTo !== null) {
            $movements = array_values(array_filter(
                $movements,
                static fn($m) => $m->createdAt <= $dateTo . ' 23:59:59',
            ));
        }

        return $this->json(['data' => $movements]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/movements/{id}', name: 'api_inventory_movements_show', methods: ['GET'])]
    public function showMovement(
        string $id,
        FindMovementByIdHandler $handler,
    ): JsonResponse {
        try {
            $movement = $handler($id);

            return $this->json(['data' => $movement]);
        } catch (\RuntimeException) {
            return $this->json(['error' => 'Movimiento no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/movements', name: 'api_inventory_movements_create', methods: ['POST'])]
    public function createMovement(
        Request $request,
        CreateTransferHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        $user      = $this->getUser();
        $createdBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new CreateTransferCommand(
                destinationLocationId: trim((string) ($payload['destinationLocationId'] ?? '')),
                items:                 $payload['items'] ?? [],
                notes:                 isset($payload['notes']) ? trim((string) $payload['notes']) : null,
                createdBy:             $createdBy,
            ));

            return $this->json(['data' => ['status' => 'created']], Response::HTTP_CREATED);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/movements/{id}/confirm', name: 'api_inventory_movements_confirm', methods: ['PATCH'])]
    public function confirmMovement(
        string $id,
        ConfirmTransferHandler $handler,
    ): JsonResponse {
        $user        = $this->getUser();
        $confirmedBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new ConfirmTransferCommand(
                movementId:   $id,
                confirmedBy:  $confirmedBy,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'confirmed']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/movements/{id}/cancel', name: 'api_inventory_movements_cancel', methods: ['PATCH'])]
    public function cancelMovement(
        string $id,
        CancelTransferHandler $handler,
    ): JsonResponse {
        $user        = $this->getUser();
        $cancelledBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new CancelTransferCommand(
                movementId:   $id,
                cancelledBy:  $cancelledBy,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'cancelled']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/movements/{id}/reject', name: 'api_inventory_movements_reject', methods: ['PATCH'])]
    public function rejectMovement(
        string $id,
        Request $request,
        RejectTransferHandler $handler,
    ): JsonResponse {
        $payload    = $request->toArray();
        $user       = $this->getUser();
        $rejectedBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new RejectTransferCommand(
                movementId: $id,
                rejectedBy: $rejectedBy,
                reason:     trim((string) ($payload['reason'] ?? '')),
                quantity:   (int) ($payload['quantity'] ?? 1),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'rejected']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
