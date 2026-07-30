<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\AtmModel\AtmModel;
use App\Support\Domain\Exceptions\ModuleNodeNotFoundException;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class ModuleNode
{
    private Collection $children;
    private ?DateTimeImmutable $updatedAt = null;

    private function __construct(
        private ModuleNodeIdValueObject $id,
        private string $model,
        private string $code,
        private string $label,
        private ?ModuleNode $parent,
        private int $position,
        private DateTimeImmutable $createdAt,
        private array $components = [],
    ) {
        $this->children = new ArrayCollection();
    }

    public static function create(
        ModuleNodeIdValueObject $id,
        AtmModel $model,
        string $code,
        string $label,
        array $components = [],
        ?ModuleNode $parent = null,
        int $position = 0,
    ): self {
        if (trim($code) === '') {
            throw new \InvalidArgumentException('El código del módulo es obligatorio.');
        }
        if (trim($label) === '') {
            throw new \InvalidArgumentException('El nombre del módulo es obligatorio.');
        }

        $node = new self(
            id: $id,
            model: $model->value,
            code: strtoupper(trim($code)),
            label: trim($label),
            parent: $parent,
            position: $position,
            createdAt: new DateTimeImmutable(),
            components: $components,
        );

        if ($parent !== null) {
            $parent->addChild($node);
        }

        return $node;
    }

    public function update(string $label, array $components = []): void
    {
        if (trim($label) === '') {
            throw new \InvalidArgumentException('El nombre del módulo es obligatorio.');
        }

        $this->label = trim($label);
        $this->components = $components;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addChild(ModuleNode $child): void
    {
        $this->children->add($child);
    }

    public function removeChild(ModuleNode $child): void
    {
        $this->children->removeElement($child);
    }

    public function moveTo(?ModuleNode $newParent): void
    {
        if ($this->parent !== null) {
            $this->parent->removeChild($this);
        }

        $this->parent = $newParent;

        if ($newParent !== null) {
            $newParent->addChild($this);
        }

        $this->updatedAt = new DateTimeImmutable();
    }

    public function id(): ModuleNodeIdValueObject
    {
        return $this->id;
    }

    public function model(): string
    {
        return $this->model;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function parent(): ?ModuleNode
    {
        return $this->parent;
    }

    public function parentId(): ?string
    {
        return $this->parent?->id?->__toString();
    }

    public function position(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function components(): array
    {
        return $this->components;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function children(): array
    {
        return $this->children->toArray();
    }
}
