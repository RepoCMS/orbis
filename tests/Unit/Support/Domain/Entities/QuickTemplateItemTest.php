<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Entities\QuickTemplateItem;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class QuickTemplateItemTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TEMPLATE_ID = '01HZ0000000000000000000002';

    private function createQuickTemplate(): QuickTemplate
    {
        return QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::TEMPLATE_ID),
            modelId: 'MODEL-001',
            name: 'Plantilla de prueba',
            createdBy: 'user-001',
            description: null,
        );
    }

    public function testRegisterFactoryCreatesCorrectly(): void
    {
        $template = $this->createQuickTemplate();

        $item = QuickTemplateItem::register(
            id: QuickTemplateItemIdValueObject::fromString(self::ID),
            quickTemplate: $template,
            modelPartId: 'PART-001',
            partNumber: 'PN-001',
            productCode: 'PROD-001',
            description: 'Revisar módulo de depósito',
            quantity: 2,
            notes: 'Verificar estado',
            sortOrder: 1,
        );

        self::assertSame(self::ID, (string) $item->id());
        self::assertSame('PART-001', $item->modelPartId());
        self::assertSame('PN-001', $item->partNumber());
        self::assertSame('PROD-001', $item->productCode());
        self::assertSame('Revisar módulo de depósito', $item->description());
        self::assertSame(2, $item->quantity());
        self::assertSame('Verificar estado', $item->notes());
        self::assertSame(1, $item->sortOrder());
        self::assertInstanceOf(\DateTimeImmutable::class, $item->createdAt());
        self::assertSame($template, $item->quickTemplate());
    }

    public function testRegisterWithMinimalParams(): void
    {
        $template = $this->createQuickTemplate();

        $item = QuickTemplateItem::register(
            id: QuickTemplateItemIdValueObject::fromString(self::ID),
            quickTemplate: $template,
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Tarea básica',
        );

        self::assertNull($item->modelPartId());
        self::assertNull($item->partNumber());
        self::assertNull($item->productCode());
        self::assertSame('Tarea básica', $item->description());
        self::assertSame(1, $item->quantity());
        self::assertNull($item->notes());
        self::assertSame(0, $item->sortOrder());
    }

    public function testRegisterThrowsOnEmptyDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La descripción del item es obligatoria');

        QuickTemplateItem::register(
            id: QuickTemplateItemIdValueObject::fromString(self::ID),
            quickTemplate: $this->createQuickTemplate(),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: '',
        );
    }

    public function testRegisterThrowsOnQuantityLessThanOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La cantidad debe ser al menos 1');

        QuickTemplateItem::register(
            id: QuickTemplateItemIdValueObject::fromString(self::ID),
            quickTemplate: $this->createQuickTemplate(),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Tarea',
            quantity: 0,
        );
    }

    public function testRegisterThrowsOnNegativeSortOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('El orden debe ser un valor no negativo');

        QuickTemplateItem::register(
            id: QuickTemplateItemIdValueObject::fromString(self::ID),
            quickTemplate: $this->createQuickTemplate(),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Tarea',
            sortOrder: -1,
        );
    }

    public function testUpdate(): void
    {
        $item = QuickTemplateItem::register(
            id: QuickTemplateItemIdValueObject::fromString(self::ID),
            quickTemplate: $this->createQuickTemplate(),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Descripción original',
            quantity: 1,
            sortOrder: 1,
        );

        $item->update(
            modelPartId: 'PART-002',
            partNumber: 'PN-002',
            productCode: 'PROD-002',
            description: 'Descripción actualizada',
            quantity: 3,
            notes: 'Nota actualizada',
            sortOrder: 5,
        );

        self::assertSame('PART-002', $item->modelPartId());
        self::assertSame('PN-002', $item->partNumber());
        self::assertSame('PROD-002', $item->productCode());
        self::assertSame('Descripción actualizada', $item->description());
        self::assertSame(3, $item->quantity());
        self::assertSame('Nota actualizada', $item->notes());
        self::assertSame(5, $item->sortOrder());
    }

    public function testUpdateWithNullOptionalFields(): void
    {
        $item = QuickTemplateItem::register(
            id: QuickTemplateItemIdValueObject::fromString(self::ID),
            quickTemplate: $this->createQuickTemplate(),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Original',
            quantity: 1,
            sortOrder: 0,
        );

        $item->update(
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Actualizada',
            quantity: 1,
            notes: null,
            sortOrder: 0,
        );

        self::assertNull($item->modelPartId());
        self::assertNull($item->partNumber());
        self::assertNull($item->productCode());
        self::assertSame('Actualizada', $item->description());
    }
}
