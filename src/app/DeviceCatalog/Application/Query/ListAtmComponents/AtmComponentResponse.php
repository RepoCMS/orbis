<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Application\Query\ListAtmComponents;

use App\DeviceCatalog\Domain\AtmComponent;

final readonly class AtmComponentResponse
{
    public function __construct(
        public string  $id,
        public ?string $parentId,
        public string  $name,
        public ?string $aliases,
        public string  $level,
        public int     $sortOrder,
        public bool    $active,
    ) {}

    public static function fromEntity(AtmComponent $c): self
    {
        return new self(
            id:        (string) $c->id(),
            parentId:  $c->parentId() ? (string) $c->parentId() : null,
            name:      $c->name(),
            aliases:   $c->aliases(),
            level:     $c->level()->value,
            sortOrder: $c->sortOrder(),
            active:    $c->isActive(),
        );
    }
}
