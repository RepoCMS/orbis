<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\EventListener;

use App\Shared\Exceptions\DomainException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Listener para convertir automáticamente DomainExceptions
 * en respuestas HTTP 400 Bad Request con formato JSON.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
final class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Solo maneja excepciones de dominio
        if (!$exception instanceof DomainException) {
            return;
        }

        $response = new JsonResponse([
            'success' => false,
            'error' => $exception->getMessage(),
            'details' => $exception->getContext(),
            'type' => $this->getExceptionType($exception),
        ], $this->getStatusCode($exception));

        $event->setResponse($response);
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        // Por defecto, las excepciones de dominio son 400 Bad Request
        return 400;
    }

    private function getExceptionType(\Throwable $exception): string
    {
        $reflection = new \ReflectionClass($exception);

        return $reflection->getShortName();
    }
}
