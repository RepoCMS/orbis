<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteSubLocation;

final readonly class DeleteSubLocationCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
