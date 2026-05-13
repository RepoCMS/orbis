<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use DateTimeImmutable;

final class Product
{
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        private ProductIdValueObject $id,
        private ProductCodeAssignedBySupplierValueObject $productCodeBySupplier,
        private ProductNameValueObject $name,
        private string $description,
        private ProductTrackingType $trackingType,  
    ) {
    }

    public static function add(
        ProductIdValueObject $id,
        ProductCodeAssignedBySupplierValueObject $productCode,
        ProductNameValueObject $name,
        string $description,
        ProductTrackingType $trackingType,
    ): self {
        return new self($id, $productCode, $name, $description, $trackingType);
    }

    public function id(): ProductIdValueObject
    {
        return $this->id;
    }

    public function productCode(): ProductCodeAssignedBySupplierValueObject
    {
        return $this->productCodeBySupplier;
    }

    public function name(): ProductNameValueObject
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function trackingType(): ProductTrackingType
    {
        return $this->trackingType;
    }

    public function isSerialized(): bool
    {
        return $this->trackingType === ProductTrackingType::SERIALIZED;
    }

    public function update(ProductNameValueObject $name, string $description): void
    {
        $this->name        = $name;
        $this->description = $description;
    }

    public function changeTrackingType(ProductTrackingType $newType): void
    {
        $this->trackingType = $newType;
        }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }
}