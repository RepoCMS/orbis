<?php

declare(strict_types=1);

namespace App\Support\Application\Command\DeleteQuickTemplate;

use App\Support\Application\Exception\QuickTemplateNotFoundException;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;

final readonly class DeleteQuickTemplateHandler
{
    public function __construct(
        private QuickTemplateRepositoryInterface $templateRepo,
    ) {}

    public function __invoke(DeleteQuickTemplateCommand $command): void
    {
        $templateId = QuickTemplateIdValueObject::fromString($command->templateId);

        $template = $this->templateRepo->findById($templateId);
        if ($template === null) {
            throw QuickTemplateNotFoundException::withId($command->templateId);
        }

        $this->templateRepo->delete($template);
    }
}
