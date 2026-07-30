<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Exception\QuickTemplateNotFoundException;
use App\Support\Application\Query\FindQuickTemplate\FindQuickTemplateHandler;
use App\Support\Application\Query\FindQuickTemplate\FindQuickTemplateQuery;
use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindQuickTemplateHandlerTest extends TestCase
{
    private const TEMPLATE_ID = '01HZ0000000000000000000001';
    private const CREATED_BY  = '01HZ0000000000000000000002';

    public function testFindsTemplate(): void
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::TEMPLATE_ID),
            modelId: 'model-1',
            name: 'Plantilla de prueba',
            createdBy: self::CREATED_BY,
            description: 'Descripción de prueba',
        );

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn($template);

        $handler = new FindQuickTemplateHandler($templateRepo);
        $result = ($handler)(new FindQuickTemplateQuery(templateId: self::TEMPLATE_ID));

        self::assertSame(self::TEMPLATE_ID, $result->id);
        self::assertSame('Plantilla de prueba', $result->name);
        self::assertSame('model-1', $result->modelId);
        self::assertSame('Descripción de prueba', $result->description);
        self::assertTrue($result->isActive);
    }

    public function testThrowsWhenTemplateNotFound(): void
    {
        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn(null);

        $handler = new FindQuickTemplateHandler($templateRepo);

        $this->expectException(QuickTemplateNotFoundException::class);

        ($handler)(new FindQuickTemplateQuery(templateId: '01HZ0000000000000000000099'));
    }
}
