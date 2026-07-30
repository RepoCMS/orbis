<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Exceptions\ApplicationException;
use App\Support\Application\Command\UpdateQuickTemplate\UpdateQuickTemplateCommand;
use App\Support\Application\Command\UpdateQuickTemplate\UpdateQuickTemplateHandler;
use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateQuickTemplateHandlerTest extends TestCase
{
    private const TEMPLATE_ID = '01HZ0000000000000000000001';
    private const MODEL_ID    = '01HZ0000000000000000000002';
    private const CREATED_BY  = '01HZ0000000000000000000003';

    private function makeTemplate(): QuickTemplate
    {
        return QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::TEMPLATE_ID),
            modelId: self::MODEL_ID,
            name: 'Nombre original',
            createdBy: self::CREATED_BY,
            description: 'Descripción original',
        );
    }

    public function testUpdatesNameAndDescription(): void
    {
        $template = $this->makeTemplate();

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn($template);
        $templateRepo->expects($this->once())->method('save')->with($template);

        $handler = new UpdateQuickTemplateHandler($templateRepo);

        ($handler)(new UpdateQuickTemplateCommand(
            templateId: self::TEMPLATE_ID,
            name: 'Nombre actualizado',
            description: 'Descripción actualizada',
        ));

        self::assertSame('Nombre actualizado', $template->name());
        self::assertSame('Descripción actualizada', $template->description());
    }

    public function testUpdatesNameOnly(): void
    {
        $template = $this->makeTemplate();

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn($template);
        $templateRepo->expects($this->once())->method('save')->with($template);

        $handler = new UpdateQuickTemplateHandler($templateRepo);

        ($handler)(new UpdateQuickTemplateCommand(
            templateId: self::TEMPLATE_ID,
            name: 'Solo nombre',
        ));

        self::assertSame('Solo nombre', $template->name());
        self::assertSame('Descripción original', $template->description());
        self::assertTrue($template->isActive());
    }

    public function testActivatesTemplate(): void
    {
        $template = $this->makeTemplate();
        $template->deactivate();
        self::assertFalse($template->isActive());

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn($template);
        $templateRepo->expects($this->once())->method('save')->with($template);

        $handler = new UpdateQuickTemplateHandler($templateRepo);

        ($handler)(new UpdateQuickTemplateCommand(
            templateId: self::TEMPLATE_ID,
            isActive: true,
        ));

        self::assertTrue($template->isActive());
    }

    public function testDeactivatesTemplate(): void
    {
        $template = $this->makeTemplate();
        self::assertTrue($template->isActive());

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn($template);
        $templateRepo->expects($this->once())->method('save')->with($template);

        $handler = new UpdateQuickTemplateHandler($templateRepo);

        ($handler)(new UpdateQuickTemplateCommand(
            templateId: self::TEMPLATE_ID,
            isActive: false,
        ));

        self::assertFalse($template->isActive());
    }

    public function testTemplateNotFoundThrows(): void
    {
        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn(null);

        $handler = new UpdateQuickTemplateHandler($templateRepo);

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Plantilla rápida con ID "' . self::TEMPLATE_ID . '" no encontrada.');

        ($handler)(new UpdateQuickTemplateCommand(
            templateId: self::TEMPLATE_ID,
        ));
    }
}
