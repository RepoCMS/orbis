<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * Asserts the route exists (does not return 404).
     * For protected routes, returns 302 (redirect to login) when unauthenticated.
     */
    public static function assertRouteExists(Response $response): void
    {
        static::assertNotSame(404, $response->getStatusCode(),
            \sprintf('Route not found (404). URL may be invalid. Response: %s', $response->getContent() ?: '(empty)'));
    }

    public static function assertResponseIsRedirectToLogin(Response $response): void
    {
        $isRedirect = $response->isRedirect() || $response->getStatusCode() === 302;
        static::assertTrue($isRedirect,
            \sprintf('Expected redirect (302) to login, got %d: %s', $response->getStatusCode(), $response->getContent() ?: '(empty)'));
    }

    /**
     * @param array<string, mixed> $server
     */
    protected function jsonRequest(KernelBrowser $client, string $method, string $uri, ?array $content = null, array $server = []): Response
    {
        $defaultServer = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        $client->request(
            $method,
            $uri,
            [],
            [],
            \array_merge($defaultServer, $server),
            $content !== null ? \json_encode($content, \JSON_THROW_ON_ERROR) : null,
        );

        return $client->getResponse();
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonResponse(Response $response): array
    {
        $content = $response->getContent();
        $this->assertNotFalse($content, 'Response content is false');

        $data = \json_decode($content, true);
        $this->assertIsArray($data, \sprintf('Response is not valid JSON: %s', $content));

        return $data;
    }
}
