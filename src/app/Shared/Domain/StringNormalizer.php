<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Utilidad para normalización de strings.
 * Proporciona métodos estáticos para normalizar diferentes tipos de cadenas de texto.
 */
final class StringNormalizer
{
    private function __construct()
    {
        // Clase estática, no instanciable
    }

    /**
     * Normaliza un nombre eliminando espacios múltiples y aplicando trim.
     *
     * @param string $value El valor a normalizar
     * @return string El valor normalizado
     */
    public static function normalizeName(string $value): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim($value));
    }

    /**
     * Normaliza un email convirtiéndolo a minúsculas y aplicando trim.
     *
     * @param string $value El email a normalizar
     * @return string El email normalizado
     */
    public static function normalizeEmail(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * Normaliza un número de teléfono eliminando espacios, guiones y paréntesis.
     *
     * @param string $value El número de teléfono a normalizar
     * @return string El número normalizado (solo dígitos)
     */
    public static function normalizePhoneNumber(string $value): string
    {
        return preg_replace('/[\s\-\(\)]/', '', trim($value));
    }
}
