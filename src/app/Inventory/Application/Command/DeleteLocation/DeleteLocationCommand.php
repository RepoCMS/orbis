<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteLocation;

final readonly class DeleteLocationCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
