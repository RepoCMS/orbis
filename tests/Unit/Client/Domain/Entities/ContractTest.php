<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\Entities;

use App\Client\Domain\Entities\Contract;
use App\Client\Domain\Enums\ContractStatus;
use App\Client\Domain\Exceptions\ContractStatusException;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ContractTest extends TestCase
{
    private function createContract(): Contract
    {
        return Contract::create(
            new ContractIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-12-31'),
            'Términos del contrato',
            'Mantenimiento preventivo',
        );
    }

    public function testCreateStoresAllFields(): void
    {
        $contract = $this->createContract();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $contract->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $contract->clientId());
        self::assertSame('2025-01-01', $contract->startDate()->format('Y-m-d'));
        self::assertSame('2025-12-31', $contract->endDate()->format('Y-m-d'));
        self::assertSame('Términos del contrato', $contract->terms());
        self::assertSame('Mantenimiento preventivo', $contract->scope());
        self::assertSame(ContractStatus::ACTIVE, $contract->status());
        self::assertTrue($contract->isActive());
    }

    public function testCreateWithDefaultStatus(): void
    {
        $contract = Contract::create(
            new ContractIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new DateTimeImmutable('2025-01-01'),
        );

        self::assertSame(ContractStatus::ACTIVE, $contract->status());
        self::assertNull($contract->endDate());
        self::assertNull($contract->terms());
        self::assertNull($contract->scope());
    }

    public function testCancelSetsCancelledStatus(): void
    {
        $contract = $this->createContract();

        $contract->cancel();

        self::assertSame(ContractStatus::CANCELLED, $contract->status());
        self::assertFalse($contract->isActive());
    }

    public function testCancelThrowsWhenNotActive(): void
    {
        $contract = $this->createContract();
        $contract->cancel();

        $this->expectException(ContractStatusException::class);
        $this->expectExceptionMessage('Solo se puede cancelar un contrato activo.');

        $contract->cancel();
    }

    public function testUpdateTermsModifiesValue(): void
    {
        $contract = $this->createContract();

        $contract->updateTerms('Nuevos términos');

        self::assertSame('Nuevos términos', $contract->terms());
    }

    public function testUpdateTermsToNullClearsIt(): void
    {
        $contract = $this->createContract();

        $contract->updateTerms(null);

        self::assertNull($contract->terms());
    }

    public function testUpdateScopeModifiesValue(): void
    {
        $contract = $this->createContract();

        $contract->updateScope('Nuevo alcance');

        self::assertSame('Nuevo alcance', $contract->scope());
    }

    public function testUpdateScopeToNullClearsIt(): void
    {
        $contract = $this->createContract();

        $contract->updateScope(null);

        self::assertNull($contract->scope());
    }

    public function testExtendEndDateUpdatesValue(): void
    {
        $contract = $this->createContract();
        $newEnd = new DateTimeImmutable('2026-06-30');

        $contract->extendEndDate($newEnd);

        self::assertSame('2026-06-30', $contract->endDate()->format('Y-m-d'));
    }

    public function testExtendEndDateToNullClearsIt(): void
    {
        $contract = $this->createContract();

        $contract->extendEndDate(null);

        self::assertNull($contract->endDate());
    }
}
