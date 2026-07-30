<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CreateQuickTemplate;

use App\Client\Domain\Repositories\TerminalModelRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalModelIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Exception\TerminalModelNotFoundException;
use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;

final readonly class CreateQuickTemplateHandler
{
    public function __construct(
        private QuickTemplateRepositoryInterface $templateRepo,
        private TerminalModelRepositoryInterface $terminalModelRepo,
        private IdGeneratorInterface $idGenerator,
    ) {}

    public function __invoke(CreateQuickTemplateCommand $command): string
    {
        $modelId = TerminalModelIdValueObject::fromString($command->modelId);
        $model = $this->terminalModelRepo->findById($modelId);

        if ($model === null) {
            throw TerminalModelNotFoundException::withId($command->modelId);
        }

        $templateId = QuickTemplateIdValueObject::fromString($this->idGenerator->generate());

        $template = QuickTemplate::create(
            id:          $templateId,
            modelId:     $command->modelId,
            name:        $command->name,
            createdBy:   $command->createdBy,
            description: $command->description,
        );

        foreach ($command->items as $itemData) {
            $template->addItem(
                id:          QuickTemplateItemIdValueObject::fromString($this->idGenerator->generate()),
                modelPartId: $itemData['modelPartId'] ?? null,
                partNumber:  $itemData['partNumber'] ?? null,
                productCode: $itemData['productCode'] ?? null,
                description: $itemData['description'],
                quantity:    $itemData['quantity'] ?? 1,
                notes:       $itemData['notes'] ?? null,
                sortOrder:   $itemData['sortOrder'] ?? 0,
            );
        }

        $this->templateRepo->save($template);

        return (string) $template->id();
    }
}
