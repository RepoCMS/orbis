<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Entities\QuickTemplateItem;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class QuickTemplateTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const MODEL_ID = '01HZ0000000000000000000002';
    private const CREATED_BY = '01HZ0000000000000000000003';
    private const ITEM_ID = '01HZ0000000000000000000004';

    public function testCreateFactoryCreatesCorrectly(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla Preventivo NCR',
            createdBy: self::CREATED_BY,
        );

        self::assertSame(self::ID, (string) $template->id());
        self::assertSame(self::MODEL_ID, $template->modelId());
        self::assertSame('Plantilla Preventivo NCR', $template->name());
        self::assertNull($template->description());
        self::assertTrue($template->isActive());
        self::assertSame(self::CREATED_BY, $template->createdBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $template->createdAt());
        self::assertNull($template->updatedAt());
        self::assertEmpty($template->items());
    }

    public function testCreateWithDescription(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla Preventivo',
            createdBy: self::CREATED_BY,
            description: 'Mantenimiento preventivo estándar',
        );

        self::assertSame('Mantenimiento preventivo estándar', $template->description());
    }

    public function testCreateThrowsOnEmptyModelId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del modelo de terminal es obligatorio');

        QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: '',
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre de la plantilla es obligatorio');

        QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: '',
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyCreatedBy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El creador de la plantilla es obligatorio');

        QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: '',
        );
    }

    public function testAddItem(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );

        $template->addItem(
            id: QuickTemplateItemIdValueObject::fromString(self::ITEM_ID),
            modelPartId: 'PART-001',
            partNumber: 'PN-001',
            productCode: 'PROD-001',
            description: 'Revisar módulo de depósito',
            quantity: 1,
            notes: 'Verificar estado',
            sortOrder: 1,
        );

        self::assertCount(1, $template->items());
        $item = $template->items()[0];
        self::assertInstanceOf(QuickTemplateItem::class, $item);
        self::assertSame('Revisar módulo de depósito', $item->description());
        self::assertSame(1, $item->sortOrder());
    }

    public function testRemoveItem(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );

        $template->addItem(
            id: QuickTemplateItemIdValueObject::fromString(self::ITEM_ID),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Item 1',
            quantity: 1,
            sortOrder: 1,
        );

        self::assertCount(1, $template->items());

        $template->removeItem(QuickTemplateItemIdValueObject::fromString(self::ITEM_ID));

        self::assertCount(0, $template->items());
    }

    public function testRemoveItemThrowsWhenNotFound(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item no encontrado en la plantilla');

        $template->removeItem(QuickTemplateItemIdValueObject::fromString('01HZ0000000000000000000099'));
    }

    public function testUpdateItem(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );

        $template->addItem(
            id: QuickTemplateItemIdValueObject::fromString(self::ITEM_ID),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Descripción original',
            quantity: 1,
            sortOrder: 1,
        );

        $template->updateItem(
            itemId: QuickTemplateItemIdValueObject::fromString(self::ITEM_ID),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Descripción actualizada',
            quantity: 2,
            notes: 'Nota nueva',
            sortOrder: 3,
        );

        $item = $template->items()[0];
        self::assertSame('Descripción actualizada', $item->description());
        self::assertSame(2, $item->quantity());
        self::assertSame('Nota nueva', $item->notes());
        self::assertSame(3, $item->sortOrder());
    }

    public function testUpdateItemThrowsWhenNotFound(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item no encontrado en la plantilla');

        $template->updateItem(
            itemId: QuickTemplateItemIdValueObject::fromString('01HZ0000000000000000000099'),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Test',
            quantity: 1,
            notes: null,
            sortOrder: 0,
        );
    }

    public function testActivateAndDeactivate(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );

        self::assertTrue($template->isActive());

        $template->deactivate();
        self::assertFalse($template->isActive());

        $template->activate();
        self::assertTrue($template->isActive());
    }

    public function testRename(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Nombre original',
            createdBy: self::CREATED_BY,
        );

        $template->rename('Nombre actualizado');

        self::assertSame('Nombre actualizado', $template->name());
    }

    public function testRenameThrowsOnEmptyName(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Nombre original',
            createdBy: self::CREATED_BY,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre de la plantilla es obligatorio');

        $template->rename('');
    }

    public function testUpdateDescription(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );

        $template->updateDescription('Nueva descripción');
        self::assertSame('Nueva descripción', $template->description());

        $template->updateDescription(null);
        self::assertNull($template->description());
    }

    public function testAddMultipleItems(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::ID),
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        );

        $template->addItem(
            id: QuickTemplateItemIdValueObject::fromString(self::ITEM_ID),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Item 1',
            quantity: 1,
            sortOrder: 1,
        );
        $template->addItem(
            id: QuickTemplateItemIdValueObject::fromString('01HZ0000000000000000000005'),
            modelPartId: null,
            partNumber: null,
            productCode: null,
            description: 'Item 2',
            quantity: 2,
            sortOrder: 2,
        );

        self::assertCount(2, $template->items());
    }
}
