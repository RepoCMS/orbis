<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use DateTimeImmutable;

final class Location
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;
    private ?string $createdBy = null;
    private ?string $updatedBy = null;
    private ?string $deletedBy = null;

    private function __construct(
        private LocationIdValueObject $id,
        private LocationNameValueObject $name,
        private LocationType $type,
        private string $city,
        private string $address,
        private ?string $employeeId = null,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        LocationIdValueObject $id,
        LocationNameValueObject $name,
        string $city,
        string $address,
        LocationType $type,
    ): self {
        return new self($id, $name, $type, $city, $address);
    }

    public function id(): LocationIdValueObject
    {
        return $this->id;
    }

    public function name(): LocationNameValueObject
    {
        return $this->name;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function address(): string
    {
        return $this->address;
    }

    public function type(): LocationType
    {
        return $this->type;
    }

    public function employeeId(): ?string
    {
        return $this->employeeId;
    }

    public function isWarehouse(): bool
    {
        return $this->type === LocationType::WAREHOUSE;
    }

    public function isLaboratory(): bool
    {
        return $this->type === LocationType::LABORATORY;
    }

    public function isBackpack(): bool
    {
        return $this->type === LocationType::BACKPACK;
    }

    public function isCustody(): bool
    {
        return $this->type === LocationType::CUSTODY;
    }

    public function update(string $name, string $city, string $address, ?string $employeeId = null): void
    {
        $this->name      = new LocationNameValueObject($name);
        $this->city      = $city;
        $this->address   = $address;
        $this->updatedAt = new DateTimeImmutable();

        if ($employeeId !== null) {
            $this->employeeId = $employeeId;
        }
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
