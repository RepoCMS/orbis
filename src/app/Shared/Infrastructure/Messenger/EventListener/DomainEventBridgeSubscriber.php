<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\EventListener;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Bridge subscriber que captura TODOS los Domain Events desde el EventDispatcher
 * de Symfony y los redirige al bus de Messenger para procesamiento async.
 *
 * Esto permite que los cross-BC event handlers se ejecuten de forma asíncrona
 * a través de Messenger, en lugar de sincrónicamente en el mismo proceso.
 *
 * Los handlers específicos (en Expense, Organization, Notification, etc.)
 * reciben el DomainEventMessage y filtran por eventClass para procesar
 * solo los eventos que les interesan.
 */
#[AsEventListener(event: DomainEvent::class)]
final class DomainEventBridgeSubscriber
{
    /**
     * Flag estático para evitar re-despacho recursivo.
     * Cuando el handler de Messenger re-despacha el evento (en sync transport),
     * este flag previene que el bridge lo capture nuevamente.
     */
    private static bool $isReplaying = false;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(DomainEvent $event): void
    {
        if (self::$isReplaying) {
            return;
        }

        $message = DomainEventMessage::fromEvent($event);

        $this->messageBus->dispatch($message);
    }

    /**
     * Permite que los handlers marquen que están re-despachando un evento
     * para evitar loops. Usado internamente cuando el handler re-despacha
     * el evento al EventDispatcher.
     */
    public static function setReplaying(bool $replaying): void
    {
        self::$isReplaying = $replaying;
    }
}
