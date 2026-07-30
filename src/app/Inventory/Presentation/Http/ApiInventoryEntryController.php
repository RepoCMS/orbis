<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\AddEntryItem\AddEntryItemCommand;
use App\Inventory\Application\Command\AddEntryItem\AddEntryItemHandler;
use App\Inventory\Application\Command\CancelEntry\CancelEntryCommand;
use App\Inventory\Application\Command\CancelEntry\CancelEntryHandler;
use App\Inventory\Application\Command\ConfirmEntry\ConfirmEntryCommand;
use App\Inventory\Application\Command\ConfirmEntry\ConfirmEntryHandler;
use App\Inventory\Application\Command\CreateEntry\CreateEntryCommand;
use App\Inventory\Application\Command\CreateEntry\CreateEntryHandler;
use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Application\Query\FindEntryById\FindEntryByIdHandler;
use App\Inventory\Application\Query\ListEntries\ListEntriesHandler;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory/entries')]
final class ApiInventoryEntryController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'api_inventory_entries_list', methods: ['GET'])]
    public function listEntries(
        Request $request,
        ListEntriesHandler $handler,
    ): JsonResponse {
        $status    = $request->query->get('status');
        $reference = $request->query->get('reference');

        $entries = $handler();

        if ($status !== null) {
            $entries = array_values(array_filter(
                $entries,
                static fn($e) => $e->status === $status,
            ));
        }

        if ($reference !== null) {
            $q = mb_strtolower($reference);
            $entries = array_values(array_filter(
                $entries,
                static fn($e) => str_contains(mb_strtolower($e->reference), $q),
            ));
        }

        return $this->json(['data' => $entries]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'api_inventory_entries_create', methods: ['POST'])]
    public function createEntry(
        Request $request,
        CreateEntryHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        $user      = $this->getUser();
        $createdBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new CreateEntryCommand(
                reference: trim((string) ($payload['reference'] ?? '')),
                notes:     isset($payload['notes']) ? trim((string) $payload['notes']) : null,
                createdBy: $createdBy,
            ));

            return $this->json(['data' => ['status' => 'created']], Response::HTTP_CREATED);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'api_inventory_entries_show', methods: ['GET'])]
    public function showEntry(
        string $id,
        FindEntryByIdHandler $handler,
    ): JsonResponse {
        try {
            $data = $handler($id);

            return $this->json([
                'data' => [
                    'entry' => $data['entry'],
                    'items' => $data['items'],
                ],
            ]);
        } catch (EntryNotFoundException) {
            return $this->json(['error' => 'Entrada de inventario no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('inventory.create')]
    #[Route('/{id}/items', name: 'api_inventory_entries_add_item', methods: ['POST'])]
    public function addEntryItem(
        string $id,
        Request $request,
        AddEntryItemHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new AddEntryItemCommand(
                entryId:             $id,
                productId:           trim((string) ($payload['productId'] ?? '')),
                locationId:          trim((string) ($payload['locationId'] ?? '')),
                serialNumber:        isset($payload['serialNumber']) ? trim((string) $payload['serialNumber']) : null,
                quantity:            (int) ($payload['quantity'] ?? 1),
                purchaseOrderItemId: isset($payload['purchaseOrderItemId']) ? trim((string) $payload['purchaseOrderItemId']) : null,
            ));

            return $this->json(['data' => ['status' => 'item_added']]);
        } catch (EntryNotFoundException) {
            return $this->json(['error' => 'Entrada de inventario no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.create')]
    #[Route('/{id}/confirm', name: 'api_inventory_entries_confirm', methods: ['POST'])]
    public function confirmEntry(
        string $id,
        ConfirmEntryHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new ConfirmEntryCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'confirmed']]);
        } catch (EntryNotFoundException) {
            return $this->json(['error' => 'Entrada de inventario no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/cancel', name: 'api_inventory_entries_cancel', methods: ['POST'])]
    public function cancelEntry(
        string $id,
        CancelEntryHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new CancelEntryCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'cancelled']]);
        } catch (EntryNotFoundException) {
            return $this->json(['error' => 'Entrada de inventario no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
