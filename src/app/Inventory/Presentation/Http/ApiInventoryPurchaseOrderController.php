<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Inventory\Application\Command\AddPurchaseOrderItem\AddPurchaseOrderItemCommand;
use App\Inventory\Application\Command\AddPurchaseOrderItem\AddPurchaseOrderItemHandler;
use App\Inventory\Application\Command\CancelPurchaseOrder\CancelPurchaseOrderCommand;
use App\Inventory\Application\Command\CancelPurchaseOrder\CancelPurchaseOrderHandler;
use App\Inventory\Application\Command\CreatePurchaseOrderFromSuggestion\CreatePurchaseOrderFromSuggestionCommand;
use App\Inventory\Application\Command\CreatePurchaseOrderFromSuggestion\CreatePurchaseOrderFromSuggestionHandler;
use App\Inventory\Application\Command\CreatePurchaseOrderWithItems\CreatePurchaseOrderWithItemsCommand;
use App\Inventory\Application\Command\CreatePurchaseOrderWithItems\CreatePurchaseOrderWithItemsHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Application\Query\FindPurchaseOrderById\FindPurchaseOrderByIdHandler;
use App\Inventory\Application\Query\GeneratePurchaseSuggestions\GeneratePurchaseSuggestionsHandler;
use App\Inventory\Application\Query\GeneratePurchaseSuggestions\GeneratePurchaseSuggestionsQuery;
use App\Inventory\Application\Query\ListPurchaseOrders\ListPurchaseOrdersHandler;
use App\Inventory\Application\Query\ListPurchaseOrders\ListPurchaseOrdersQuery;
use App\Inventory\Application\Query\Response\PurchaseSuggestionResponse;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory')]
final class ApiInventoryPurchaseOrderController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('/purchase-orders', name: 'api_inventory_purchase_orders_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListPurchaseOrdersHandler $handler,
    ): JsonResponse {
        $status    = $request->query->get('status');
        $reference = $request->query->get('reference');

        $purchaseOrders = $handler(new ListPurchaseOrdersQuery(
            status:    $status !== null ? (string) $status : null,
            reference: $reference !== null ? (string) $reference : null,
        ));

        return $this->json(['data' => $purchaseOrders]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/purchase-orders', name: 'api_inventory_purchase_orders_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreatePurchaseOrderWithItemsHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $user    = $this->getUser();
        $userId  = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            $items = [];
            if (isset($payload['items']) && is_array($payload['items'])) {
                foreach ($payload['items'] as $item) {
                    $items[] = [
                        'productId'      => trim((string) ($item['productId'] ?? '')),
                        'quantityOrdered' => (int) ($item['quantityOrdered'] ?? 1),
                    ];
                }
            }

            ($handler)(new CreatePurchaseOrderWithItemsCommand(
                reference: trim((string) ($payload['reference'] ?? '')),
                notes:     isset($payload['notes']) ? trim((string) $payload['notes']) : null,
                createdBy: $userId !== '' ? $userId : trim((string) ($payload['createdBy'] ?? '')),
                items:     $items,
            ));

            return $this->json(
                ['data' => ['reference' => trim((string) ($payload['reference'] ?? ''))]],
                Response::HTTP_CREATED,
            );
        } catch (PurchaseOrderReferenceAlreadyExistsException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ProductNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/purchase-orders/{id}', name: 'api_inventory_purchase_orders_show', methods: ['GET'])]
    public function show(
        string $id,
        FindPurchaseOrderByIdHandler $handler,
    ): JsonResponse {
        try {
            $data = $handler($id);

            return $this->json(['data' => $data]);
        } catch (PurchaseOrderNotFoundException) {
            return $this->json(['error' => 'Orden de compra no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('inventory.create')]
    #[Route('/purchase-orders/{id}/items', name: 'api_inventory_purchase_orders_add_item', methods: ['POST'])]
    public function addItem(
        string $id,
        Request $request,
        AddPurchaseOrderItemHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new AddPurchaseOrderItemCommand(
                purchaseOrderId: $id,
                productId:       trim((string) ($payload['productId'] ?? '')),
                quantityOrdered: (int) ($payload['quantityOrdered'] ?? 1),
            ));

            return $this->json(['data' => ['purchaseOrderId' => $id]]);
        } catch (PurchaseOrderNotFoundException) {
            return $this->json(['error' => 'Orden de compra no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (ProductNotFoundException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/purchase-orders/{id}/cancel', name: 'api_inventory_purchase_orders_cancel', methods: ['POST'])]
    public function cancel(
        string $id,
        CancelPurchaseOrderHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new CancelPurchaseOrderCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'CANCELLED']]);
        } catch (PurchaseOrderNotFoundException) {
            return $this->json(['error' => 'Orden de compra no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/purchase-suggestions', name: 'api_inventory_purchase_suggestions', methods: ['GET'])]
    public function suggestions(
        GeneratePurchaseSuggestionsHandler $handler,
    ): JsonResponse {
        $suggestions  = $handler(new GeneratePurchaseSuggestionsQuery());
        $responseData = array_map(
            static fn ($suggestion) => PurchaseSuggestionResponse::fromSuggestion($suggestion),
            $suggestions,
        );

        return $this->json(['data' => $responseData]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/purchase-suggestions/{productId}/{locationId}/create-po', name: 'api_inventory_purchase_suggestions_create_po', methods: ['POST'])]
    public function createPoFromSuggestion(
        string $productId,
        string $locationId,
        Request $request,
        CreatePurchaseOrderFromSuggestionHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $user   = $this->getUser();
            $userId = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

            ($handler)(new CreatePurchaseOrderFromSuggestionCommand(
                productId:         $productId,
                locationId:        $locationId,
                suggestedQuantity: (int) ($payload['suggestedQuantity'] ?? 1),
                reference:         trim((string) ($payload['reference'] ?? '')),
                notes:             isset($payload['notes']) ? trim((string) $payload['notes']) : null,
                createdBy:         $userId,
            ));

            return $this->json(
                ['data' => ['reference' => trim((string) ($payload['reference'] ?? ''))]],
                Response::HTTP_CREATED,
            );
        } catch (PurchaseOrderReferenceAlreadyExistsException | ProductNotFoundException | LocationNotFoundException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
