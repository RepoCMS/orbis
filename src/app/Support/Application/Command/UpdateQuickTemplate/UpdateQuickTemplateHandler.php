<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UpdateQuickTemplate;

use App\Support\Application\Exception\QuickTemplateNotFoundException;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;

final readonly class UpdateQuickTemplateHandler
{
    public function __construct(
        private QuickTemplateRepositoryInterface $templateRepo,
    ) {}

    public function __invoke(UpdateQuickTemplateCommand $command): void
    {
        $templateId = QuickTemplateIdValueObject::fromString($command->templateId);

        $template = $this->templateRepo->findById($templateId);
        if ($template === null) {
            throw QuickTemplateNotFoundException::withId($command->templateId);
        }

        if ($command->name !== null) {
            $template->rename($command->name);
        }

        if ($command->description !== null) {
            $template->updateDescription($command->description);
        }

        if ($command->isActive === true) {
            $template->activate();
        } elseif ($command->isActive === false) {
            $template->deactivate();
        }

        $this->templateRepo->save($template);
    }
}
