<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

/**
 * Excepción base para violaciones de reglas de negocio del dominio.
 * Representa errores recuperables causados por datos de entrada inválidos.
 */
abstract class DomainException extends RuntimeException
{
    protected array $context = [];

    /**
     * Agrega contexto adicional a la excepción.
     *
     * @param array $context Datos estructurados adicionales (field, value, constraints, etc.)
     * @return $this Para usar fluent interface
     */
    public function withContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Obtiene el contexto de la excepción.
     *
     * @return array Datos de contexto estructurados
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Serializa la excepción a un array para logging o respuestas API.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => static::class,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
