<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UpdateQuickTemplate;

final readonly class UpdateQuickTemplateCommand
{
    public function __construct(
        public string  $templateId,
        public ?string $name = null,
        public ?string $description = null,
        public ?bool   $isActive = null,
    ) {}
}
