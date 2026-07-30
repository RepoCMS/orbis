<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ApplyQuickTemplate;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Exception\QuickTemplateNotFoundException;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\TicketPendingSparePart;
use App\Support\Domain\Entities\TicketSparePart;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\Repositories\TicketPendingSparePartRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\Repositories\TicketSparePartRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPendingSparePartIdValueObject;
use App\Support\Domain\ValueObjects\TicketSparePartIdValueObject;

final readonly class ApplyQuickTemplateHandler
{
    public function __construct(
        private QuickTemplateRepositoryInterface            $templateRepo,
        private TicketRepositoryInterface                   $ticketRepo,
        private TicketSparePartRepositoryInterface           $sparePartRepo,
        private TicketPendingSparePartRepositoryInterface    $pendingSparePartRepo,
        private IdGeneratorInterface                        $idGenerator,
    ) {}

    /**
     * @return array{sparePartIds: string[], pendingSparePartIds: string[]}
     */
    public function __invoke(ApplyQuickTemplateCommand $command): array
    {
        $templateId = QuickTemplateIdValueObject::fromString($command->templateId);
        $ticketId   = TicketIdValueObject::fromString($command->ticketId);

        $template = $this->templateRepo->findById($templateId);
        if ($template === null) {
            throw QuickTemplateNotFoundException::withId($command->templateId);
        }

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw TicketNotFoundException::withId($command->ticketId);
        }

        $findingId = TicketFindingIdValueObject::fromString($command->findingId);
        $sparePartIds = [];
        $pendingSparePartIds = [];

        foreach ($template->items() as $item) {
            if ($item->productCode() !== null) {
                $sparePart = TicketSparePart::register(
                    id:                TicketSparePartIdValueObject::fromString($this->idGenerator->generate()),
                    ticketId:          $ticketId,
                    findingId:         $findingId,
                    productCode:       $item->productCode(),
                    serialNumber:      null,
                    quantity:          $item->quantity(),
                    isIncoming:        true,
                    isVisibleToClient: true,
                );

                $this->sparePartRepo->save($sparePart);
                $sparePartIds[] = (string) $sparePart->id();
            } else {
                $pendingCode = $item->partNumber() ?? substr($item->description(), 0, 50);

                $pending = TicketPendingSparePart::register(
                    id:          TicketPendingSparePartIdValueObject::fromString($this->idGenerator->generate()),
                    ticketId:    $ticketId,
                    productCode: $pendingCode,
                    quantity:    $item->quantity(),
                    notes:       sprintf('Desde plantilla "%s": %s', $template->name(), $item->description()),
                );

                $this->pendingSparePartRepo->save($pending);
                $pendingSparePartIds[] = (string) $pending->id();
            }
        }

        return [
            'sparePartIds'       => $sparePartIds,
            'pendingSparePartIds' => $pendingSparePartIds,
        ];
    }
}
