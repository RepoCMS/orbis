<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreatePurchaseOrderWithItems;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\Events\PurchaseOrderCreated;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
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
 * Handler que crea una orden de compra con items opcionales en una sola operación.
 *
 * Valida que la referencia no esté duplicada, que los productos existan,
 * y persiste tanto la orden como sus items en una transacción.
 * Publica el evento PurchaseOrderCreated al finalizar.
 */
final readonly class CreatePurchaseOrderWithItemsHandler
{
    use DbExceptionHandlerTrait;
    use IdempotentHandlerTrait;

    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private PurchaseOrderItemRepositoryInterface $purchaseOrderItemRepository,
        private ProductRepositoryInterface $productRepository,
        private DomainEventPublisherInterface $eventPublisher,
        private IdGeneratorInterface $idGenerator,
        private IdempotencyServiceInterface $idempotencyService,
    ) {
    }

    public function __invoke(CreatePurchaseOrderWithItemsCommand $command): void
    {
        $this->ensureUnique(
            IdempotencyService::buildKey(CreatePurchaseOrderWithItemsCommand::class, serialize($command)),
            CreatePurchaseOrderWithItemsCommand::class,
            $command->createdBy,
        );

        // Validar referencia única
        $existing = $this->purchaseOrderRepository->findByReference($command->reference);
        if ($existing !== null) {
            throw PurchaseOrderReferenceAlreadyExistsException::withReference($command->reference);
        }

        // Validar productos antes de la escritura
        foreach ($command->items as $itemData) {
            $productId = new ProductIdValueObject($itemData['productId']);
            $product   = $this->productRepository->findById($productId);
            if ($product === null) {
                throw ProductNotFoundException::withId($itemData['productId']);
            }
        }

        $this->wrapDbExceptions(function () use ($command): void {
            // Crear la orden de compra
            $poId = new PurchaseOrderIdValueObject($this->idGenerator->generate());
            $purchaseOrder = PurchaseOrder::create(
                id:        $poId,
                reference: $command->reference,
                notes:     $command->notes,
                createdBy: new UserIdValueObject($command->createdBy),
            );

            $this->purchaseOrderRepository->save($purchaseOrder);

            // Crear items si se proporcionaron
            foreach ($command->items as $itemData) {
                $productId = new ProductIdValueObject($itemData['productId']);

                $item = PurchaseOrderItem::create(
                    id:               new PurchaseOrderItemIdValueObject($this->idGenerator->generate()),
                    purchaseOrderId:  $poId,
                    productId:        $productId,
                    quantityOrdered:  $itemData['quantityOrdered'],
                );

                $this->purchaseOrderItemRepository->save($item);
            }

            // Publicar evento de dominio
            $this->eventPublisher->publish(new PurchaseOrderCreated(
                purchaseOrderId: (string) $poId,
            ));
        });
    }
}
