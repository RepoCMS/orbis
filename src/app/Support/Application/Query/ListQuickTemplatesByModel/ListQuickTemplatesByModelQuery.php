<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListQuickTemplatesByModel;

final readonly class ListQuickTemplatesByModelQuery
{
    public function __construct(
        public string $modelId,
        public bool $onlyActive = false,
    ) {
    }
}
