<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Service;

use App\Notification\Application\Service\TemplateVariableResolver;
use App\Notification\Domain\Services\ContactResolverInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TemplateVariableResolver.
 *
 * Fase 7: P2 Mejoras (T7.1 - TemplateVariableResolver)
 */
final class TemplateVariableResolverTest extends TestCase
{
    private const RECIPIENT_TYPE = 'employee';
    private const RECIPIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FB1';

    private function createResolver(array $contactData = []): TemplateVariableResolver
    {
        $contactResolver = $this->createMock(ContactResolverInterface::class);
        $contactResolver->method('resolve')
            ->with(self::RECIPIENT_TYPE, self::RECIPIENT_ID)
            ->willReturn($contactData);

        return new TemplateVariableResolver($contactResolver);
    }

    public function testResolveReplacesDefaultVariables(): void
    {
        $resolver = $this->createResolver([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '+593991234567',
        ]);

        $result = $resolver->resolve(
            'Hola {{employeeName}}, tu email es {{recipientEmail}} y tu teléfono {{recipientPhone}}.',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
        );

        self::assertSame(
            'Hola Juan Pérez, tu email es juan@example.com y tu teléfono +593991234567.',
            $result,
        );
    }

    public function testResolveReplacesAdditionalVariables(): void
    {
        $resolver = $this->createResolver([
            'name' => 'María García',
        ]);

        $result = $resolver->resolve(
            'Ticket {{ticketNumber}} creado para {{serviceType}}.',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
            [
                'ticketNumber' => 'TKT-001',
                'serviceType' => 'mantenimiento preventivo',
            ],
        );

        self::assertSame(
            'Ticket TKT-001 creado para mantenimiento preventivo.',
            $result,
        );
    }

    public function testResolveKeepsUnresolvedVariables(): void
    {
        $resolver = $this->createResolver([]);

        $result = $resolver->resolve(
            'Hola {{unknownVar}}, tu solicitud {{reference}} está lista.',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
            ['reference' => 'SOL-001'],
        );

        // {{unknownVar}} has no matching key, so it stays as-is
        // {{reference}} is resolved
        self::assertSame(
            'Hola {{unknownVar}}, tu solicitud SOL-001 está lista.',
            $result,
        );
    }

    public function testResolveWithEmptyTemplateVars(): void
    {
        $resolver = $this->createResolver([
            'name' => 'Carlos López',
        ]);

        $result = $resolver->resolve(
            'Hola {{employeeName}}, bienvenido.',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
            [],
        );

        self::assertSame('Hola Carlos López, bienvenido.', $result);
    }

    public function testResolveUsesRecipientNameAsFallback(): void
    {
        $resolver = $this->createResolver([
            'name' => 'Ana Torres',
        ]);

        $result = $resolver->resolve(
            'Hola {{recipientName}}!',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
        );

        self::assertSame('Hola Ana Torres!', $result);
    }

    public function testResolveWithNoContactData(): void
    {
        $resolver = $this->createResolver([]);

        $result = $resolver->resolve(
            'Hola {{employeeName}}!',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
        );

        // employeeName defaults to empty string
        self::assertSame('Hola !', $result);
    }

    public function testResolveMultipleOccurrences(): void
    {
        $resolver = $this->createResolver([
            'name' => 'Pedro',
        ]);

        $result = $resolver->resolve(
            '{{employeeName}} dijo {{employeeName}} otra vez.',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
        );

        self::assertSame('Pedro dijo Pedro otra vez.', $result);
    }

    public function testResolveWithMixedVariablesAndDefaults(): void
    {
        $resolver = $this->createResolver([
            'name' => 'Laura',
            'email' => 'laura@example.com',
        ]);

        $result = $resolver->resolve(
            '{{employeeName}} ({{recipientEmail}}) - Referencia: {{reference}}',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
            ['reference' => 'REF-001'],
        );

        self::assertSame(
            'Laura (laura@example.com) - Referencia: REF-001',
            $result,
        );
    }

    public function testResolveRecipientNameDiffersFromEmployeeName(): void
    {
        $resolver = $this->createResolver([
            'name' => 'Contacto Cliente',
        ]);

        $result = $resolver->resolve(
            'Hola {{recipientName}} (antes conocido como {{employeeName}}).',
            self::RECIPIENT_TYPE,
            self::RECIPIENT_ID,
        );

        // Both recipientName and employeeName resolve to 'name' from contact
        self::assertSame(
            'Hola Contacto Cliente (antes conocido como Contacto Cliente).',
            $result,
        );
    }
}
