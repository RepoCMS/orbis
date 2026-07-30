<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateSubLocation;

final readonly class CreateSubLocationCommand
{
    public function __construct(
        public string $locationId,
        public string $type,
        public string $name,
        public string $code,
        public ?string $parentId = null,
    ) {
    }
}
