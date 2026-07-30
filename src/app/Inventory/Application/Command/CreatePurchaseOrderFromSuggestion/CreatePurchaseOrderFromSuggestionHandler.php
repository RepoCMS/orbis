<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreatePurchaseOrderFromSuggestion;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use App\Shared\Application\IdempotentHandlerTrait;
use App\Shared\Application\Service\IdempotencyService;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

/**
 * Crea una orden de compra con un item pre-cargado desde una sugerencia.
 *
 * Valida que el producto y la ubicación existan, y que la referencia no esté duplicada.
 * Pre-carga el producto como item de la orden con la cantidad sugerida.
 */
final readonly class CreatePurchaseOrderFromSuggestionHandler
{
    use DbExceptionHandlerTrait;
    use IdempotentHandlerTrait;

    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private PurchaseOrderItemRepositoryInterface $purchaseOrderItemRepository,
        private ProductRepositoryInterface $productRepository,
        private LocationRepositoryInterface $locationRepository,
        private DomainEventPublisherInterface $eventPublisher,
        private IdGeneratorInterface $idGenerator,
        private IdempotencyServiceInterface $idempotencyService,
    ) {
    }

    public function __invoke(CreatePurchaseOrderFromSuggestionCommand $command): void
    {
        $this->ensureUnique(
            IdempotencyService::buildKey(CreatePurchaseOrderFromSuggestionCommand::class, serialize($command)),
            CreatePurchaseOrderFromSuggestionCommand::class,
            $command->createdBy,
        );

        // Validar referencia única
        $existing = $this->purchaseOrderRepository->findByReference($command->reference);
        if ($existing !== null) {
            throw PurchaseOrderReferenceAlreadyExistsException::withReference($command->reference);
        }

        // Validar producto
        $productId = new ProductIdValueObject($command->productId);
        $product   = $this->productRepository->findById($productId);
        if ($product === null) {
            throw ProductNotFoundException::withId($command->productId);
        }

        // Validar ubicación
        $locationId = new LocationIdValueObject($command->locationId);
        $location   = $this->locationRepository->findById($locationId);
        if ($location === null) {
            throw LocationNotFoundException::withId($command->locationId);
        }

        $this->wrapDbExceptions(function () use ($command, $productId, $locationId): void {
            // Crear la orden de compra
            $poId = new PurchaseOrderIdValueObject($this->idGenerator->generate());
            $purchaseOrder = PurchaseOrder::create(
                id:        $poId,
                reference: $command->reference,
                notes:     $command->notes,
                createdBy: new UserIdValueObject($command->createdBy),
            );

            $this->purchaseOrderRepository->save($purchaseOrder);

            // Crear el item con el producto sugerido y la cantidad calculada
            $item = PurchaseOrderItem::create(
                id:               new PurchaseOrderItemIdValueObject($this->idGenerator->generate()),
                purchaseOrderId:  $poId,
                productId:        $productId,
                quantityOrdered:  $command->suggestedQuantity,
            );

            $this->purchaseOrderItemRepository->save($item);

            // Publicar evento de dominio
            $this->eventPublisher->publish(new \App\Inventory\Domain\Events\PurchaseOrderCreated(
                purchaseOrderId: (string) $poId,
            ));
        });
    }
}
