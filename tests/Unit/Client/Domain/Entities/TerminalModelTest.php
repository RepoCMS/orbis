<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\Entities;

use App\Client\Domain\Entities\TerminalModel;
use App\Client\Domain\Exceptions\TerminalModelNameException;
use App\Client\Domain\ValueObjects\TerminalModelIdValueObject;
use PHPUnit\Framework\TestCase;

final class TerminalModelTest extends TestCase
{
    private function createModel(): TerminalModel
    {
        return TerminalModel::register(
            new TerminalModelIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            'MX 8200 ST',
            'Modelo de ATM de alta capacidad',
        );
    }

    public function testRegisterStoresAllFields(): void
    {
        $model = $this->createModel();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $model->id());
        self::assertSame('MX 8200 ST', $model->name());
        self::assertSame('Modelo de ATM de alta capacidad', $model->description());
        self::assertNotNull($model->createdAt());
        self::assertNull($model->updatedAt());
        self::assertNull($model->deletedAt());
        self::assertFalse($model->isDeleted());
    }

    public function testRegisterWithoutDescription(): void
    {
        $model = TerminalModel::register(
            new TerminalModelIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            'MX 5800 T',
        );

        self::assertNull($model->description());
    }

    public function testRegisterTrimsName(): void
    {
        $model = TerminalModel::register(
            new TerminalModelIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            '  MX 8200 ST  ',
        );

        self::assertSame('MX 8200 ST', $model->name());
    }

    public function testRegisterThrowsWhenNameIsEmpty(): void
    {
        $this->expectException(TerminalModelNameException::class);
        $this->expectExceptionMessage('El nombre del modelo es obligatorio.');

        TerminalModel::register(
            new TerminalModelIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            '',
        );
    }

    public function testRenameUpdatesValue(): void
    {
        $model = $this->createModel();

        $model->rename('MX 8200 ST v2');

        self::assertSame('MX 8200 ST v2', $model->name());
        self::assertNotNull($model->updatedAt());
    }

    public function testRenameThrowsWhenEmpty(): void
    {
        $model = $this->createModel();

        $this->expectException(TerminalModelNameException::class);
        $this->expectExceptionMessage('El nombre del modelo es obligatorio.');

        $model->rename('  ');
    }

    public function testRenameTrimsValue(): void
    {
        $model = $this->createModel();

        $model->rename('  MX 8200 ST v3  ');

        self::assertSame('MX 8200 ST v3', $model->name());
    }

    public function testUpdateDescriptionChangesValue(): void
    {
        $model = $this->createModel();

        $model->updateDescription('Nueva descripción');

        self::assertSame('Nueva descripción', $model->description());
        self::assertNotNull($model->updatedAt());
    }

    public function testUpdateDescriptionToNullClearsIt(): void
    {
        $model = $this->createModel();

        $model->updateDescription(null);

        self::assertNull($model->description());
    }

    public function testUpdateDescriptionWithBlankStringClearsIt(): void
    {
        $model = $this->createModel();

        $model->updateDescription('  ');

        self::assertNull($model->description());
    }

    public function testSoftDeleteSetsDeletedAt(): void
    {
        $model = $this->createModel();

        $model->softDelete();

        self::assertTrue($model->isDeleted());
        self::assertNotNull($model->deletedAt());
    }
}
