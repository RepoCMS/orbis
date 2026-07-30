<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\SubLocationType;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Sub-ubicación jerárquica que sub-divide una Location.
 *
 * Jerarquía: RACK (nivel 1) → SHELF (nivel 2) → POSITION (nivel 3).
 * Máximo 3 niveles de profundidad. POSITION no puede tener hijos.
 */
final class SubLocation
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;

    /** @var Collection<int, SubLocation> */
    private Collection $children;

    private function __construct(
        private SubLocationIdValueObject $id,
        private string $locationId,
        private SubLocationType $type,
        private string $name,
        private string $code,
        private ?self $parent,
        private int $depth,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->children = new ArrayCollection();
    }

    public static function create(
        SubLocationIdValueObject $id,
        string $locationId,
        SubLocationType $type,
        string $name,
        string $code,
        ?self $parent = null,
    ): self {
        if ($parent !== null && !$parent->type->canHaveChildren()) {
            throw new InventoryDomainException('No se pueden agregar hijos a una posición.');
        }

        $depth = $parent !== null ? $parent->depth + 1 : 1;

        if ($depth > 3) {
            throw new InventoryDomainException('La profundidad máxima de sub-ubicación es 3.');
        }

        if ($depth === 3 && $type !== SubLocationType::POSITION) {
            throw new InventoryDomainException('En el nivel 3 solo se permiten posiciones.');
        }

        return new self($id, $locationId, $type, $name, $code, $parent, $depth);
    }

    // ─── Getters ──────────────────────────────────────────────────────────────

    public function id(): SubLocationIdValueObject
    {
        return $this->id;
    }

    public function locationId(): string
    {
        return $this->locationId;
    }

    public function type(): SubLocationType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function parent(): ?self
    {
        return $this->parent;
    }

    public function parentId(): ?string
    {
        return $this->parent !== null ? (string) $this->parent->id : null;
    }

    public function depth(): int
    {
        return $this->depth;
    }

    /** @return SubLocation[] */
    public function children(): array
    {
        return $this->children->toArray();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    // ─── State queries ────────────────────────────────────────────────────────

    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    public function isLeaf(): bool
    {
        return $this->type === SubLocationType::POSITION || $this->children->isEmpty();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    // ─── Mutations ────────────────────────────────────────────────────────────

    public function addChild(self $child): void
    {
        if (!$this->type->canHaveChildren()) {
            throw new InventoryDomainException('No se pueden agregar hijos a una posición.');
        }

        if ($this->depth + 1 > 3) {
            throw new InventoryDomainException('La profundidad máxima de sub-ubicación es 3.');
        }

        if ($this->depth + 1 === 3 && $child->type !== SubLocationType::POSITION) {
            throw new InventoryDomainException('En el nivel 3 solo se permiten posiciones.');
        }

        $this->children->add($child);
    }

    public function update(string $name, string $code): void
    {
        $this->name = $name;
        $this->code = $code;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }
}
