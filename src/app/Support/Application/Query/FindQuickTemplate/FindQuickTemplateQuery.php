<?php

declare(strict_types=1);

namespace App\Support\Application\Query\FindQuickTemplate;

final readonly class FindQuickTemplateQuery
{
    public function __construct(
        public string $templateId,
    ) {
    }
}
