<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Service;

use App\Notification\Domain\Services\ContactResolverInterface;

final readonly class ChainContactResolver implements ContactResolverInterface
{
    /** @param ContactResolverInterface[] $resolvers */
    public function __construct(
        private iterable $resolvers,
    ) {}

    public function resolve(string $recipientType, string $recipientId): array
    {
        foreach ($this->resolvers as $resolver) {
            $result = $resolver->resolve($recipientType, $recipientId);

            if ($result['email'] !== null || $result['phone'] !== null) {
                return $result;
            }
        }

        return ['email' => null, 'phone' => null, 'name' => null];
    }
}
