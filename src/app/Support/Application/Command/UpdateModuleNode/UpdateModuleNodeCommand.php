<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UpdateModuleNode;

final readonly class UpdateModuleNodeCommand
{
    /**
     * @param string[] $components
     */
    public function __construct(
        public string $id,
        public string $label,
        public array $components = [],
    ) {
    }
}
