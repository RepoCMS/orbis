<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\SubLocation;

final readonly class SubLocationResponse
{
    /** @param SubLocationResponse[] $children */
    public function __construct(
        public string $id,
        public string $locationId,
        public string $type,
        public string $typeLabel,
        public string $name,
        public string $code,
        public ?string $parentId,
        public int $depth,
        public array $children = [],
    ) {
    }

    /** @param SubLocation[] $roots */
    public static function fromTree(array $roots): array
    {
        return array_map(
            static fn (SubLocation $root) => self::fromEntityWithChildren($root),
            $roots,
        );
    }

    private static function fromEntityWithChildren(SubLocation $subLocation): self
    {
        return new self(
            id:         (string) $subLocation->id(),
            locationId: $subLocation->locationId(),
            type:       $subLocation->type()->value,
            typeLabel:  $subLocation->type()->label(),
            name:       $subLocation->name(),
            code:       $subLocation->code(),
            parentId:   $subLocation->parentId(),
            depth:      $subLocation->depth(),
            children:   array_map(
                static fn (SubLocation $child) => self::fromEntityWithChildren($child),
                $subLocation->children(),
            ),
        );
    }
}
