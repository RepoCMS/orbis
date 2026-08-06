<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\Entities;

use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Enums\ContactStatus;
use App\Client\Domain\Exceptions\ContactNameException;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use PHPUnit\Framework\TestCase;

final class ContactTest extends TestCase
{
    private function createContact(?UserIdValueObject $userId = null): Contact
    {
        return Contact::create(
            new ContactIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            'Juan Carlos',
            'Perez Lopez',
            new ContactEmailValueObject('juan@empresa.com'),
            new ContactPhoneNumberValueObject('0991234567'),
            new ContactRoleValueObject('Supervisor de ATMs'),
            $userId,
        );
    }

    // --- Creacion ---

    public function testCreateStoresAllFields(): void
    {
        $contact = $this->createContact();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $contact->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $contact->clientId());
        self::assertSame('Juan Carlos', $contact->firstName());
        self::assertSame('Perez Lopez', $contact->lastName());
        self::assertSame('Juan Carlos Perez Lopez', $contact->fullName());
        self::assertSame('juan@empresa.com', (string) $contact->email());
        self::assertSame('0991234567', (string) $contact->phoneNumber());
        self::assertSame('Supervisor de ATMs', (string) $contact->role());
        self::assertSame(ContactStatus::ACTIVE, $contact->status());
        self::assertNull($contact->userId());
        self::assertFalse($contact->hasUser());
        self::assertFalse($contact->isDeleted());
    }

    public function testCreateWithUser(): void
    {
        $userId = new UserIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5XYZ');
        $contact = $this->createContact($userId);

        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5XYZ', (string) $contact->userId());
        self::assertTrue($contact->hasUser());
    }

    public function testCreateNormalizesNames(): void
    {
        $contact = Contact::create(
            new ContactIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            '  Juan   Carlos  ',
            '  Perez   Lopez  ',
            new ContactEmailValueObject('juan@empresa.com'),
            new ContactPhoneNumberValueObject('0991234567'),
            new ContactRoleValueObject('Supervisor'),
        );

        self::assertSame('Juan Carlos', $contact->firstName());
        self::assertSame('Perez Lopez', $contact->lastName());
    }

    // --- Validacion de nombre ---

    public function testCreateThrowsWhenFirstNameTooShort(): void
    {
        $this->expectException(ContactNameException::class);

        Contact::create(
            new ContactIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            'A',
            'Perez Lopez',
            new ContactEmailValueObject('juan@empresa.com'),
            new ContactPhoneNumberValueObject('0991234567'),
            new ContactRoleValueObject('Supervisor'),
        );
    }

    public function testCreateThrowsWhenLastNameEmpty(): void
    {
        $this->expectException(ContactNameException::class);

        Contact::create(
            new ContactIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            'Juan Carlos',
            '',
            new ContactEmailValueObject('juan@empresa.com'),
            new ContactPhoneNumberValueObject('0991234567'),
            new ContactRoleValueObject('Supervisor'),
        );
    }

    // --- Change Name ---

    public function testChangeNameUpdatesValues(): void
    {
        $contact = $this->createContact();

        $contact->changeName('Maria Fernanda', 'Gonzalez');

        self::assertSame('Maria Fernanda', $contact->firstName());
        self::assertSame('Gonzalez', $contact->lastName());
    }

    public function testChangeNameThrowsWhenInvalid(): void
    {
        $contact = $this->createContact();

        $this->expectException(ContactNameException::class);
        $contact->changeName('X', 'Gonzalez');
    }

    // --- Change Email ---

    public function testChangeEmailUpdatesValue(): void
    {
        $contact = $this->createContact();
        $newEmail = new ContactEmailValueObject('nuevo@empresa.com');

        $contact->changeEmail($newEmail);

        self::assertSame('nuevo@empresa.com', (string) $contact->email());
    }

    // --- Change Phone ---

    public function testChangePhoneNumberUpdatesValue(): void
    {
        $contact = $this->createContact();
        $newPhone = new ContactPhoneNumberValueObject('0997654321');

        $contact->changePhoneNumber($newPhone);

        self::assertSame('0997654321', (string) $contact->phoneNumber());
    }

    // --- Change Role ---

    public function testChangeRoleUpdatesValue(): void
    {
        $contact = $this->createContact();
        $newRole = new ContactRoleValueObject('Jefe de Operaciones');

        $contact->changeRole($newRole);

        self::assertSame('Jefe de Operaciones', (string) $contact->role());
    }

    // --- Link / Unlink User ---

    public function testLinkUserSetsUserId(): void
    {
        $contact = $this->createContact();
        $userId = new UserIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5XYZ');

        $contact->linkUser($userId);

        self::assertTrue($contact->hasUser());
        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5XYZ', (string) $contact->userId());
    }

    public function testUnlinkUserClearsUserId(): void
    {
        $userId = new UserIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5XYZ');
        $contact = $this->createContact($userId);

        $contact->unlinkUser();

        self::assertFalse($contact->hasUser());
        self::assertNull($contact->userId());
    }

    // --- Status ---

    public function testDeactivateSetsInactiveStatus(): void
    {
        $contact = $this->createContact();

        $contact->deactivate();

        self::assertSame(ContactStatus::INACTIVE, $contact->status());
        self::assertFalse($contact->isActive());
    }

    public function testReactivateSetsActiveStatus(): void
    {
        $contact = $this->createContact();
        $contact->deactivate();

        $contact->reactivate();

        self::assertSame(ContactStatus::ACTIVE, $contact->status());
        self::assertTrue($contact->isActive());
    }

    // --- Soft Delete ---

    public function testSoftDeleteSetsDeletedAt(): void
    {
        $contact = $this->createContact();

        self::assertFalse($contact->isDeleted());

        $contact->softDelete();

        self::assertTrue($contact->isDeleted());
        self::assertNotNull($contact->deletedAt());
    }
}
