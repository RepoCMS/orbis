<?php

declare(strict_types=1);

namespace App\Support\Application\Command\DeleteModuleNode;

final readonly class DeleteModuleNodeCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
