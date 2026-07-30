<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

use App\Support\Domain\Entities\QuickTemplateItem;

final readonly class QuickTemplateItemResponse
{
    /**
     * @param int|null $availableStock Cantidad disponible en inventario (global, todas las ubicaciones)
     */
    public function __construct(
        public string  $id,
        public ?string $modelPartId,
        public ?string $partNumber,
        public ?string $productCode,
        public string  $description,
        public int     $quantity,
        public ?string $notes,
        public int     $sortOrder,
        public string  $createdAt,
        public ?int    $availableStock = null,
    ) {}

    public static function fromEntity(QuickTemplateItem $item, ?int $availableStock = null): self
    {
        return new self(
            id:             (string) $item->id(),
            modelPartId:    $item->modelPartId(),
            partNumber:     $item->partNumber(),
            productCode:    $item->productCode(),
            description:    $item->description(),
            quantity:       $item->quantity(),
            notes:          $item->notes(),
            sortOrder:      $item->sortOrder(),
            createdAt:      $item->createdAt()->format('Y-m-d H:i:s'),
            availableStock: $availableStock,
        );
    }
}
