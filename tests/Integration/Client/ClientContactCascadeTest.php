<?php

declare(strict_types=1);

namespace App\Tests\Integration\Client;

use App\Client\Domain\Entities\Client;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Enums\ContactStatus;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifica la persistencia y recuperación de Client y Contact
 * a través del ORM, incluyendo el ciclo de activación/desactivación
 * en cascada (simulando lo que haría el Application Service).
 */
#[Group('integration')]
final class ClientContactCascadeTest extends IntegrationTestCase
{
    private const CLIENT_ID    = '01J8XQ9W6K0000000000000001';
    private const CONTACT_1_ID = '01J8XQ9W6K0000000000000002';
    private const CONTACT_2_ID = '01J8XQ9W6K0000000000000003';

    /**
     * Crea un Client con 2 Contacts.
     * Desactiva el Client y sus Contacts.
     * Verifica que los Contacts quedaron desactivados.
     * Reactiva Client y Contacts.
     * Verifica que los Contacts reactivados.
     */
    public function testClientContactCascadeDeactivateAndReactivate(): void
    {
        $em = $this->getEntityManager();

        // ── 1. Crear Client ─────────────────────────────────────────────────
        $clientId   = new ClientIdValueObject(self::CLIENT_ID);
        $clientName = new ClientNameValueObject('Banco Test');
        $clientRuc  = new ClientRucValueObject('1790012344001');

        $client = Client::register($clientId, $clientName, $clientRuc, 'Av. Principal 555');

        $em->persist($client);

        // ── 2. Crear 2 Contacts ────────────────────────────────────────────
        $contact1 = Contact::create(
            id:        new ContactIdValueObject(self::CONTACT_1_ID),
            clientId:  $clientId,
            firstName: 'Carlos',
            lastName:  'Mendoza',
            email:     new ContactEmailValueObject('carlos.mendoza@bancotest.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0991234567'),
            role:      new ContactRoleValueObject('Supervisor ATMs'),
        );

        $contact2 = Contact::create(
            id:        new ContactIdValueObject(self::CONTACT_2_ID),
            clientId:  $clientId,
            firstName: 'María',
            lastName:  'García',
            email:     new ContactEmailValueObject('maria.garcia@bancotest.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0997654321'),
            role:      new ContactRoleValueObject('Gerente Operaciones'),
        );

        $em->persist($contact1);
        $em->persist($contact2);
        $em->flush();

        // ── 3. Verificar estado inicial ────────────────────────────────────
        self::assertTrue($client->isActive(), 'Client debe iniciar activo');
        self::assertTrue($contact1->isActive(), 'Contact 1 debe iniciar activo');
        self::assertTrue($contact2->isActive(), 'Contact 2 debe iniciar activo');

        // ── 4. Desactivar Client y Contacts (cascade) ──────────────────────
        $client->deactivate();
        $contact1->deactivate();
        $contact2->deactivate();

        $em->flush();
        $em->clear(); // Forzar recarga desde BD

        // ── 5. Recargar y verificar estado desactivado ─────────────────────
        $clientRepo  = $em->getRepository(Client::class);
        $contactRepo = $em->getRepository(Contact::class);

        /** @var Client $reloadedClient */
        $reloadedClient = $clientRepo->find(self::CLIENT_ID);
        self::assertNotNull($reloadedClient, 'Client debe existir en BD');
        self::assertFalse($reloadedClient->isActive(), 'Client debe estar inactivo');
        self::assertSame(ClientStatus::INACTIVE, $reloadedClient->status());

        /** @var Contact $reloadedContact1 */
        $reloadedContact1 = $contactRepo->find(self::CONTACT_1_ID);
        self::assertNotNull($reloadedContact1, 'Contact 1 debe existir en BD');
        self::assertFalse($reloadedContact1->isActive(), 'Contact 1 debe estar inactivo');
        self::assertSame(ContactStatus::INACTIVE, $reloadedContact1->status());

        /** @var Contact $reloadedContact2 */
        $reloadedContact2 = $contactRepo->find(self::CONTACT_2_ID);
        self::assertNotNull($reloadedContact2, 'Contact 2 debe existir en BD');
        self::assertFalse($reloadedContact2->isActive(), 'Contact 2 debe estar inactivo');
        self::assertSame(ContactStatus::INACTIVE, $reloadedContact2->status());

        // ── 6. Reactivar Client y Contacts ─────────────────────────────────
        $reloadedClient->activate();
        $reloadedContact1->reactivate();
        $reloadedContact2->reactivate();

        $em->flush();
        $em->clear();

        // ── 7. Recargar y verificar estado reactivado ──────────────────────
        /** @var Client $finalClient */
        $finalClient = $clientRepo->find(self::CLIENT_ID);
        self::assertTrue($finalClient->isActive(), 'Client debe estar activo tras reactivar');
        self::assertSame(ClientStatus::ACTIVE, $finalClient->status());

        /** @var Contact $finalContact1 */
        $finalContact1 = $contactRepo->find(self::CONTACT_1_ID);
        self::assertTrue($finalContact1->isActive(), 'Contact 1 debe estar activo tras reactivar');

        /** @var Contact $finalContact2 */
        $finalContact2 = $contactRepo->find(self::CONTACT_2_ID);
        self::assertTrue($finalContact2->isActive(), 'Contact 2 debe estar activo tras reactivar');
    }

    /**
     * Verifica que se puedan recuperar contacts por clientId.
     */
    public function testFindContactsByClientId(): void
    {
        $em = $this->getEntityManager();

        $clientId   = new ClientIdValueObject('01J8XQ9W6K0000000000000004');
        $clientName = new ClientNameValueObject('Empresa Test');
        $clientRuc  = new ClientRucValueObject('1790012344002');

        $client = Client::register($clientId, $clientName, $clientRuc);
        $em->persist($client);

        $contact = Contact::create(
            id:          new ContactIdValueObject('01J8XQ9W6K0000000000000005'),
            clientId:    $clientId,
            firstName:   'Pedro',
            lastName:    'Pérez',
            email:       new ContactEmailValueObject('pedro.perez@empresa.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0991122334'),
            role:        new ContactRoleValueObject('Contacto Principal'),
        );
        $em->persist($contact);
        $em->flush();
        $em->clear();

        // Buscar contacts por clientId usando DQL/QueryBuilder
        $contactRepo = $em->getRepository(Contact::class);
        $contacts    = $contactRepo->findBy(['clientId' => (string) $clientId]);

        self::assertCount(1, $contacts, 'Debe encontrar exactamente 1 contacto para este cliente');
        self::assertSame('Pedro', $contacts[0]->firstName());
        self::assertSame((string) $clientId, (string) $contacts[0]->clientId());
    }
}
