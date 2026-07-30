<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListQuickTemplates;

use App\Support\Application\Query\Response\QuickTemplateResponse;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;

final readonly class ListQuickTemplatesHandler
{
    public function __construct(
        private QuickTemplateRepositoryInterface $templateRepo,
    ) {}

    /** @return QuickTemplateResponse[] */
    public function __invoke(ListQuickTemplatesQuery $query): array
    {
        $templates = $this->templateRepo->findAll();

        return array_map(
            fn ($template) => QuickTemplateResponse::fromEntity($template),
            $templates,
        );
    }
}
