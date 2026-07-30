<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CreateQuickTemplate;

final readonly class CreateQuickTemplateCommand
{
    /**
     * @param array<array{modelPartId?:string|null, partNumber?:string|null, productCode?:string|null, description:string, quantity?:int, notes?:string|null, sortOrder?:int}> $items
     */
    public function __construct(
        public string $modelId,
        public string $name,
        public string $createdBy,
        public ?string $description = null,
        public array $items = [],
    ) {}
}
