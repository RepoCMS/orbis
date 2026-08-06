<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

use App\Support\Domain\Entities\QuickTemplate;

final readonly class QuickTemplateResponse
{
    /**
     * @param QuickTemplateItemResponse[] $items
     */
    public function __construct(
        public string $id,
        public string $modelId,
        public string $name,
        public ?string $description,
        public bool   $isActive,
        public string $createdBy,
        public string $createdAt,
        public ?string $updatedAt,
        public array  $items = [],
    ) {
    }

    public static function fromEntity(QuickTemplate $template): self
    {
        $items = array_map(
            fn ($item) => QuickTemplateItemResponse::fromEntity($item),
            $template->items(),
        );

        return new self(
            id:          (string) $template->id(),
            modelId:     $template->modelId(),
            name:        $template->name(),
            description: $template->description(),
            isActive:    $template->isActive(),
            createdBy:   $template->createdBy(),
            createdAt:   $template->createdAt()->format('Y-m-d H:i:s'),
            updatedAt:   $template->updatedAt()?->format('Y-m-d H:i:s'),
            items:       $items,
        );
    }
}
