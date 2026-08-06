<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

use App\Notification\Domain\Services\ContactResolverInterface;

final readonly class TemplateVariableResolver
{
    private const PATTERN = '/\{\{(\w+)\}\}/';

    public function __construct(
        private ContactResolverInterface $contactResolver,
    ) {
    }

    public function resolve(string $bodyTemplate, string $recipientType, string $recipientId, array $additionalVars = []): string
    {
        $contact = $this->contactResolver->resolve($recipientType, $recipientId);

        $defaultVars = [
            'employeeName' => $contact['name'] ?? '',
            'recipientName' => $contact['name'] ?? '',
            'recipientEmail' => $contact['email'] ?? '',
            'recipientPhone' => $contact['phone'] ?? '',
        ];

        $vars = array_merge($defaultVars, $additionalVars);

        return preg_replace_callback(self::PATTERN, static function (array $matches) use ($vars): string {
            $key = $matches[1];

            return $vars[$key] ?? $matches[0];
        }, $bodyTemplate);
    }
}
