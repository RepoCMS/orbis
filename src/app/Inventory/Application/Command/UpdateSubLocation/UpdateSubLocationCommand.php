<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdateSubLocation;

final readonly class UpdateSubLocationCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
    ) {
    }
}
