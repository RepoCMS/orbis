<?php

declare(strict_types=1);

namespace App\Support\Application\Query\FindQuickTemplate;

use App\Support\Application\Exception\QuickTemplateNotFoundException;
use App\Support\Application\Query\Response\QuickTemplateResponse;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;

final readonly class FindQuickTemplateHandler
{
    public function __construct(
        private QuickTemplateRepositoryInterface $templateRepo,
    ) {}

    public function __invoke(FindQuickTemplateQuery $query): QuickTemplateResponse
    {
        $templateId = QuickTemplateIdValueObject::fromString($query->templateId);

        $template = $this->templateRepo->findById($templateId);
        if ($template === null) {
            throw QuickTemplateNotFoundException::withId($query->templateId);
        }

        return QuickTemplateResponse::fromEntity($template);
    }
}
