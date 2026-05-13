<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entities;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Exceptions\UserStatusException;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function createUser(): User
    {
        return User::register(
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UsernameValueObject('jael.parrales'),
            new HashedPasswordValueObject('$2y$10$hashedpassword'),
            new RoleIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
        );
    }

    private function createActiveUser(): User
    {
        $user = $this->createUser();
        $user->activate();

        return $user;
    }

    // --- Registro ---

    public function testRegisterCreatesUserWithUnverifiedStatus(): void
    {
        $user = $this->createUser();

        self::assertSame(UserStatus::UNVERIFIED, $user->status());
        self::assertFalse($user->isActive());
        self::assertSame(0, $user->failedLoginAttempts());
        self::assertNull($user->lockedUntil());
        self::assertNull($user->deletedAt());
    }

    public function testRegisterStoresAllFields(): void
    {
        $user = $this->createUser();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $user->id());
        self::assertSame('jael.parrales', (string) $user->username());
        self::assertSame('$2y$10$hashedpassword', (string) $user->password());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $user->roleId());
    }

    // --- Activate ---

    public function testActivateTransitionsToActive(): void
    {
        $user = $this->createUser();
        $user->activate();

        self::assertSame(UserStatus::ACTIVE, $user->status());
        self::assertTrue($user->isActive());
    }

    public function testActivateThrowsWhenAlreadyActive(): void
    {
        $user = $this->createActiveUser();

        $this->expectException(UserStatusException::class);
        $user->activate();
    }

    public function testActivateThrowsWhenInactive(): void
    {
        $user = $this->createActiveUser();
        $user->deactivate();

        $this->expectException(UserStatusException::class);
        $user->activate();
    }

    // --- Deactivate ---

    public function testDeactivateTransitionsToInactive(): void
    {
        $user = $this->createActiveUser();
        $user->deactivate();

        self::assertSame(UserStatus::INACTIVE, $user->status());
        self::assertFalse($user->isActive());
    }

    public function testDeactivateThrowsWhenUnverified(): void
    {
        $user = $this->createUser();

        $this->expectException(UserStatusException::class);
        $user->deactivate();
    }

    public function testDeactivateThrowsWhenAlreadyInactive(): void
    {
        $user = $this->createActiveUser();
        $user->deactivate();

        $this->expectException(UserStatusException::class);
        $user->deactivate();
    }

    // --- Reactivate ---

    public function testReactivateTransitionsToActive(): void
    {
        $user = $this->createActiveUser();
        $user->deactivate();
        $user->reactivate();

        self::assertSame(UserStatus::ACTIVE, $user->status());
        self::assertTrue($user->isActive());
    }

    public function testReactivateThrowsWhenAlreadyActive(): void
    {
        $user = $this->createActiveUser();

        $this->expectException(UserStatusException::class);
        $user->reactivate();
    }

    public function testReactivateThrowsWhenUnverified(): void
    {
        $user = $this->createUser();

        $this->expectException(UserStatusException::class);
        $user->reactivate();
    }

    // --- Change Password ---

    public function testChangePasswordWhenActive(): void
    {
        $user = $this->createActiveUser();
        $newPassword = new HashedPasswordValueObject('$2y$10$newhashedpassword');

        $user->changePassword($newPassword);

        self::assertSame('$2y$10$newhashedpassword', (string) $user->password());
    }

    public function testChangePasswordResetsFailedAttempts(): void
    {
        $user = $this->createActiveUser();
        $user->recordFailedLogin();
        $user->recordFailedLogin();

        $user->changePassword(new HashedPasswordValueObject('$2y$10$new'));

        self::assertSame(0, $user->failedLoginAttempts());
    }

    public function testChangePasswordThrowsWhenNotActive(): void
    {
        $user = $this->createUser();

        $this->expectException(UserStatusException::class);
        $user->changePassword(new HashedPasswordValueObject('$2y$10$new'));
    }

    // --- Change Username ---

    public function testChangeUsernameWhenActive(): void
    {
        $user = $this->createActiveUser();
        $newUsername = new UsernameValueObject('nuevo.user');

        $user->changeUsername($newUsername);

        self::assertSame('nuevo.user', (string) $user->username());
    }

    public function testChangeUsernameThrowsWhenNotActive(): void
    {
        $user = $this->createUser();

        $this->expectException(UserStatusException::class);
        $user->changeUsername(new UsernameValueObject('otro.user'));
    }

    // --- Assign Role ---

    public function testAssignRoleUpdatesRoleId(): void
    {
        $user = $this->createActiveUser();
        $newRoleId = new RoleIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5XYZ');

        $user->assignRole($newRoleId);

        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5XYZ', (string) $user->roleId());
    }

    public function testAssignRoleWorksOnInactiveUser(): void
    {
        $user = $this->createUser();
        $newRoleId = new RoleIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5XYZ');

        $user->assignRole($newRoleId);

        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5XYZ', (string) $user->roleId());
    }

    // --- recordFailedLogin ---

    public function testRecordFailedLoginIncrementsCounter(): void
    {
        $user = $this->createActiveUser();

        $user->recordFailedLogin();
        $user->recordFailedLogin();

        self::assertSame(2, $user->failedLoginAttempts());
        self::assertNull($user->lockedUntil());
        self::assertFalse($user->isLocked());
    }

    public function testRecordFailedLoginLocksAfterFiveAttempts(): void
    {
        $user = $this->createActiveUser();

        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        self::assertSame(5, $user->failedLoginAttempts());
        self::assertNotNull($user->lockedUntil());
        self::assertTrue($user->isLocked());
    }

    public function testIsLockedReturnsFalseWhenLockedUntilIsNull(): void
    {
        $user = $this->createActiveUser();

        self::assertFalse($user->isLocked());
    }

    // --- unlock ---

    public function testUnlockClearsLockAndResetsCounter(): void
    {
        $user = $this->createActiveUser();

        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        $user->unlock();

        self::assertSame(0, $user->failedLoginAttempts());
        self::assertNull($user->lockedUntil());
        self::assertFalse($user->isLocked());
    }

    // --- softDelete ---

    public function testSoftDeleteSetsDeletedAt(): void
    {
        $user = $this->createActiveUser();

        self::assertFalse($user->isDeleted());

        $user->softDelete();

        self::assertTrue($user->isDeleted());
        self::assertNotNull($user->deletedAt());
    }

    // --- Full lifecycle ---

    public function testFullLifecycleUnverifiedToActiveToInactiveToActive(): void
    {
        $user = $this->createUser();

        self::assertSame(UserStatus::UNVERIFIED, $user->status());

        $user->activate();
        self::assertSame(UserStatus::ACTIVE, $user->status());

        $user->deactivate();
        self::assertSame(UserStatus::INACTIVE, $user->status());

        $user->reactivate();
        self::assertSame(UserStatus::ACTIVE, $user->status());
    }

    public function testFullLoginFailureFlowLockAndUnlock(): void
    {
        $user = $this->createActiveUser();

        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }
        self::assertTrue($user->isLocked());

        $user->unlock();
        self::assertFalse($user->isLocked());
        self::assertSame(0, $user->failedLoginAttempts());
    }
}