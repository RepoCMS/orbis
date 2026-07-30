<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\ValueObjects\PurchaseSuggestion;

/**
 * DTO de salida para una sugerencia de compra.
 * Serializable a JSON para la API.
 */
final readonly class PurchaseSuggestionResponse
{
    public function __construct(
        public string $productId,
        public string $productCode,
        public string $productName,
        public string $locationId,
        public string $locationName,
        public int $currentStock,
        public int $idealQuantity,
        public int $alertThreshold,
        public int $suggestedQuantity,
        public ?string $lastPurchaseDate,
        public ?float $lastPurchasePrice,
        public ?string $frequentSupplier,
        public string $generatedAt,
        public int $gap,
    ) {
    }

    public static function fromSuggestion(PurchaseSuggestion $suggestion): self
    {
        return new self(
            productId:         (string) $suggestion->productId,
            productCode:       $suggestion->productCode,
            productName:       $suggestion->productName,
            locationId:        (string) $suggestion->locationId,
            locationName:      $suggestion->locationName,
            currentStock:      $suggestion->currentStock,
            idealQuantity:     $suggestion->idealQuantity,
            alertThreshold:    $suggestion->alertThreshold,
            suggestedQuantity: $suggestion->suggestedQuantity,
            lastPurchaseDate:  $suggestion->lastPurchaseDate?->format('Y-m-d H:i:s'),
            lastPurchasePrice: $suggestion->lastPurchasePrice,
            frequentSupplier:  $suggestion->frequentSupplier,
            generatedAt:       $suggestion->generatedAt->format('Y-m-d H:i:s'),
            gap:               $suggestion->gap(),
        );
    }
}
