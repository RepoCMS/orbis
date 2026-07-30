<?php

declare(strict_types=1);

namespace App\Shared\Application\Report;

interface ReportGeneratorInterface
{
    /**
     * @return array<array<string, mixed>> Raw data rows
     */
    public function generate(array $filters = []): array;

    public function getTitle(): string;

    public function getHeaders(): array;
}
