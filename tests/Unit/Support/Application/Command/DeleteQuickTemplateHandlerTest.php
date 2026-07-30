<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Support\Application\Command\DeleteQuickTemplate\DeleteQuickTemplateCommand;
use App\Support\Application\Command\DeleteQuickTemplate\DeleteQuickTemplateHandler;
use App\Support\Application\Exception\QuickTemplateNotFoundException;
use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteQuickTemplateHandlerTest extends TestCase
{
    private const TEMPLATE_ID = '01HZ0000000000000000000001';
    private const CREATED_BY  = '01HZ0000000000000000000002';

    public function testDeletesTemplateSuccessfully(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::TEMPLATE_ID),
            modelId: 'model-1',
            name: 'Plantilla a eliminar',
            createdBy: self::CREATED_BY,
        );

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn($template);
        $templateRepo->expects($this->once())->method('delete')->with($template);

        $handler = new DeleteQuickTemplateHandler($templateRepo);
        ($handler)(new DeleteQuickTemplateCommand(templateId: self::TEMPLATE_ID));
    }

    public function testThrowsWhenTemplateNotFound(): void
    {
        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn(null);
        $templateRepo->expects($this->never())->method('delete');

        $handler = new DeleteQuickTemplateHandler($templateRepo);

        $this->expectException(QuickTemplateNotFoundException::class);

        ($handler)(new DeleteQuickTemplateCommand(templateId: '01HZ0000000000000000000099'));
    }
}
