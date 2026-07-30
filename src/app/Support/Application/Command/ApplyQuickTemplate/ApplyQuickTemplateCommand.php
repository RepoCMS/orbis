<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ApplyQuickTemplate;

final readonly class ApplyQuickTemplateCommand
{
    public function __construct(
        public string $ticketId,
        public string $findingId,
        public string $templateId,
    ) {}
}
