<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use DateTimeImmutable;

final class Location
{
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        private LocationIdValueObject $id,
        private LocationNameValueObject $name,
        private LocationType $type,
        private string $city,
        private string $address,
    ) {
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

    public function update(string $name, string $city, string $address): void
    {
        $this->name    = new LocationNameValueObject($name);
        $this->city    = $city;
        $this->address = $address;
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
