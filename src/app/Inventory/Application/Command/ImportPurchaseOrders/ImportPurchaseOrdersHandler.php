<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ImportPurchaseOrders;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\Events\PurchaseOrderCreated;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use App\Inventory\Infrastructure\Import\PurchaseOrderCsvFileParser;
use App\Inventory\Infrastructure\Import\PurchaseOrderRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ImportPurchaseOrdersHandler
{
    public function __construct(
        private PurchaseOrderCsvFileParser $parser,
        private PurchaseOrderRowValidator $rowValidator,
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private PurchaseOrderItemRepositoryInterface $purchaseOrderItemRepository,
        private ProductRepositoryInterface $productRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(ImportPurchaseOrdersCommand $command): ImportResult
    {
        $rows = $this->parser->parse($command->filePath);

        if (empty($rows)) {
            return new ImportResult(0, 0, 0, ['Archivo vacío o sin datos']);
        }

        $missingHeaders = $this->parser->validateHeaders(array_keys($rows[0]));
        if ($missingHeaders !== []) {
            return new ImportResult(0, 0, 0, [
                sprintf('Cabeceras faltantes: %s', implode(', ', $missingHeaders)),
            ]);
        }

        $imported = 0;
        $errors = 0;
        $skipped = 0;
        $errorDetail = [];

        foreach ($rows as $idx => $row) {
            $rowNumber = $idx + 2;

            $validationErrors = $this->rowValidator->validate($row, $rowNumber);
            if ($validationErrors !== []) {
                $errors++;
                $errorDetail = array_merge($errorDetail, $validationErrors);
                continue;
            }

            $reference = trim($row['reference']);

            $existing = $this->purchaseOrderRepository->findByReference($reference);
            if ($existing !== null) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: Referencia "%s" ya existe', $rowNumber, $reference);
                continue;
            }

            // Parse and validate items
            $itemSpecs = [];
            $itemParts = explode('|', trim($row['items']));
            $hasItemError = false;

            foreach ($itemParts as $i => $itemStr) {
                $parts = explode(':', $itemStr);
                $productCode = trim($parts[0]);
                $quantity = (int) trim($parts[1]);

                $productId = null;
                try {
                    $code = new ProductCodeAssignedBySupplierValueObject($productCode);
                } catch (\Throwable $e) {
                    $errors++;
                    $errorDetail[] = sprintf('Fila %d: Item #%d: código inválido "%s"', $rowNumber, $i + 1, $productCode);
                    $hasItemError = true;
                    continue;
                }

                $product = $this->productRepository->findByCode($code);
                if ($product === null) {
                    $errors++;
                    $errorDetail[] = sprintf('Fila %d: Item #%d: producto "%s" no encontrado', $rowNumber, $i + 1, $productCode);
                    $hasItemError = true;
                    continue;
                }

                $itemSpecs[] = [
                    'productId' => (string) $product->id(),
                    'quantity' => $quantity,
                ];
            }

            if ($hasItemError) {
                continue;
            }

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                $poId = new PurchaseOrderIdValueObject($this->idGenerator->generate());

                $purchaseOrder = PurchaseOrder::create(
                    id: $poId,
                    reference: $reference,
                    notes: trim($row['notes'] ?? '') ?: null,
                    createdBy: new UserIdValueObject($command->createdBy),
                );

                $this->purchaseOrderRepository->save($purchaseOrder);

                foreach ($itemSpecs as $spec) {
                    $item = PurchaseOrderItem::create(
                        id: new PurchaseOrderItemIdValueObject($this->idGenerator->generate()),
                        purchaseOrderId: $poId,
                        productId: new ProductIdValueObject($spec['productId']),
                        quantityOrdered: $spec['quantity'],
                    );
                    $this->purchaseOrderItemRepository->save($item);
                }

                $this->eventPublisher->publish(new PurchaseOrderCreated(
                    purchaseOrderId: (string) $poId,
                ));

                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error al crear orden de compra: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
