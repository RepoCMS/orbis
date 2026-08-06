<?php

declare(strict_types=1);

namespace App\Support\Application\Command\DeleteQuickTemplate;

final readonly class DeleteQuickTemplateCommand
{
    public function __construct(
        public string $templateId,
    ) {
    }
}
