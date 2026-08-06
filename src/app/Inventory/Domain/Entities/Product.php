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
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;
    private ?string $createdBy = null;
    private ?string $updatedBy = null;
    private ?string $deletedBy = null;

    private function __construct(
        private ProductIdValueObject $id,
        private ProductCodeAssignedBySupplierValueObject $productCodeBySupplier,
        private ProductNameValueObject $name,
        private string $description,
        private ProductTrackingType $trackingType,
        private ?string $lastPurchasePrice = null,
        private bool $isTransformable = false,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function add(
        ProductIdValueObject $id,
        ProductCodeAssignedBySupplierValueObject $productCode,
        ProductNameValueObject $name,
        string $description,
        ProductTrackingType $trackingType,
        ?string $lastPurchasePrice = null,
        bool $isTransformable = false,
    ): self {
        return new self($id, $productCode, $name, $description, $trackingType, $lastPurchasePrice, $isTransformable);
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

    public function lastPurchasePrice(): ?string
    {
        return $this->lastPurchasePrice;
    }

    public function updateLastPurchasePrice(string $unitCost): void
    {
        $this->lastPurchasePrice = $unitCost;
    }

    public function update(ProductNameValueObject $name, string $description): void
    {
        $this->name        = $name;
        $this->description = $description;
        $this->updatedAt   = new DateTimeImmutable();
    }

    public function changeTrackingType(ProductTrackingType $newType): void
    {
        $this->trackingType = $newType;
        $this->updatedAt    = new DateTimeImmutable();
    }

    public function isTransformable(): bool
    {
        return $this->isTransformable;
    }

    public function markAsTransformable(): void
    {
        $this->isTransformable = true;
    }

    public function markAsNotTransformable(): void
    {
        $this->isTransformable = false;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function createdBy(): ?string
    {
        return $this->createdBy;
    }

    public function updatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function deletedBy(): ?string
    {
        return $this->deletedBy;
    }

    public function setCreatedBy(string $userId): void
    {
        $this->createdBy = $userId;
    }

    public function setUpdatedBy(string $userId): void
    {
        $this->updatedBy = $userId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(?string $deletedBy = null): void
    {
        $this->deletedAt = new DateTimeImmutable();
        $this->deletedBy = $deletedBy;
    }
}
