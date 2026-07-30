<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\EventListener;

use App\Identity\Application\Command\RecordFailedLogin\RecordFailedLoginHandler;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Identity\Infrastructure\Security\LoginFailureSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

final class LoginFailureSubscriberTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testDispatchesRecordFailedLoginCommand(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();

        $request = new Request([], ['username' => 'juan.perez']);

        $event = $this->createMock(LoginFailureEvent::class);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->once())
            ->method('findByUsername')
            ->willReturn($user);
        $userRepo->expects($this->once())
            ->method('findById')
            ->willReturn($user);
        $userRepo->expects($this->once())->method('save');

        $handler = new RecordFailedLoginHandler($userRepo);

        $subscriber = new LoginFailureSubscriber($userRepo, $handler);
        $subscriber->onLoginFailure($event);

        self::assertSame(1, $user->failedLoginAttempts());
    }

    public function testIgnoresEmptyUsername(): void
    {
        $request = new Request([], ['username' => '']);

        $event = $this->createMock(LoginFailureEvent::class);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->never())->method('findByUsername');

        $handler = new RecordFailedLoginHandler($userRepo);

        $subscriber = new LoginFailureSubscriber($userRepo, $handler);
        $subscriber->onLoginFailure($event);

        self::assertTrue(true);
    }

    public function testIgnoresUserNotFound(): void
    {
        $request = new Request([], ['username' => 'unknown.user']);

        $event = $this->createMock(LoginFailureEvent::class);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->once())
            ->method('findByUsername')
            ->willReturn(null);

        $handler = new RecordFailedLoginHandler($userRepo);

        $subscriber = new LoginFailureSubscriber($userRepo, $handler);
        $subscriber->onLoginFailure($event);

        self::assertTrue(true);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = LoginFailureSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(LoginFailureEvent::class, $events);
        self::assertSame('onLoginFailure', $events[LoginFailureEvent::class]);
    }
}
