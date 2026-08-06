<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CreateModuleNode;

final readonly class CreateModuleNodeCommand
{
    /**
     * @param string[] $components
     */
    public function __construct(
        public string $model,
        public string $code,
        public string $label,
        public array $components = [],
        public ?string $parentId = null,
    ) {
    }
}
