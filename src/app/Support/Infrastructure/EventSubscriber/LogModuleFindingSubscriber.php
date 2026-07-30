<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\EventSubscriber;

use App\Support\Domain\Events\TicketModuleFindingRecorded;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber que logea cada hallazgo de módulo de ATM registrado.
 *
 * Proporciona trazabilidad de las revisiones de componentes realizadas
 * durante las asistencias técnicas.
 */
final readonly class LogModuleFindingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [TicketModuleFindingRecorded::class => 'onModuleFindingRecorded'];
    }

    public function onModuleFindingRecorded(TicketModuleFindingRecorded $event): void
    {
        $this->logger->info('Module finding recorded: {modulePath} > {componentName} = {findingType}', [
            'findingId' => $event->findingId,
            'ticketId' => $event->ticketId,
            'modulePath' => $event->modulePath,
            'componentName' => $event->componentName,
            'findingType' => $event->findingType,
            'registeredBy' => $event->registeredBy,
        ]);
    }
}
