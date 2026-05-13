<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\ActivateClient\ActivateClientCommand;
use App\Client\Application\Command\ActivateClient\ActivateClientHandler;
use App\Client\Application\Command\DeactivateClient\DeactivateClientCommand;
use App\Client\Application\Command\DeactivateClient\DeactivateClientHandler;
use App\Client\Application\Command\DeleteClient\DeleteClientCommand;
use App\Client\Application\Command\DeleteClient\DeleteClientHandler;
use App\Client\Application\Command\RegisterClient\RegisterClientCommand;
use App\Client\Application\Command\RegisterClient\RegisterClientHandler;
use App\Client\Application\Command\UpdateClient\UpdateClientCommand;
use App\Client\Application\Command\UpdateClient\UpdateClientHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\ClientRucAlreadyExistsException;
use App\Client\Application\Query\FindClientById\FindClientByIdHandler;
use App\Client\Application\Query\FindClientById\FindClientByIdQuery;
use App\Client\Application\Query\FindContactsByClient\FindContactsByClientHandler;
use App\Client\Application\Query\FindContactsByClient\FindContactsByClientQuery;
use App\Client\Application\Query\ListClients\ListClientsHandler;
use App\Client\Application\Query\ListClients\ListClientsQuery;
use App\Client\Application\Query\ListSitesByClient\ListSitesByClientHandler;
use App\Client\Application\Query\ListSitesByClient\ListSitesByClientQuery;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesHandler;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesQuery;
use App\Client\Domain\Enums\ClientStatus;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Application\Query\FindUserById\FindUserByIdHandler;
use App\Identity\Application\Query\FindUserById\FindUserByIdQuery;
use App\Identity\Application\Query\ListRoles\ListRolesHandler;
use App\Identity\Application\Query\ListRoles\ListRolesQuery;
use App\Identity\Application\Query\ListUsers\ListUsersHandler;
use App\Identity\Application\Query\ListUsers\ListUsersQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients')]
final class ClientController extends AbstractController
{
    #[IsGranted('client.view_all')]
    #[Route('', name: 'client_list', methods: ['GET'])]
    public function listClients(Request $request, ListClientsHandler $listClients): Response
    {
        $name   = $request->query->get('name') ?: null;
        $ruc    = $request->query->get('ruc') ?: null;
        $statusRaw = $request->query->get('status');
        $status = $statusRaw ? ClientStatus::tryFrom($statusRaw) : null;

        return $this->render('client/list.html.twig', [
            'clients' => $listClients(new ListClientsQuery(
                name: $name,
                ruc: $ruc,
                status: $status,
            )),
            'filters' => [
                'name'   => $name ?? '',
                'ruc'    => $ruc ?? '',
                'status' => $statusRaw ?? '',
            ],
        ]);
    }

    #[IsGranted('client.create')]
    #[Route('/new', name: 'client_new', methods: ['GET'])]
    public function newClientForm(): Response
    {
        return $this->render('client/form.html.twig', [
            'errors' => [],
            'input'  => [],
        ]);
    }

    #[IsGranted('client.create')]
    #[Route('', name: 'client_create', methods: ['POST'])]
    public function createClient(Request $request, RegisterClientHandler $register): Response
    {
        $name    = trim((string) $request->request->get('name', ''));
        $ruc     = trim((string) $request->request->get('ruc', ''));
        $address = trim((string) $request->request->get('address', '')) ?: null;

        $errors = [];

        try {
            ($register)(new RegisterClientCommand(
                name:     $name,
                ruc:      $ruc,
                address:  $address,
            ));

            $this->addFlash('success', sprintf('Cliente "%s" registrado exitosamente.', $name));

            return $this->redirectToRoute('list_clients');
        } catch (ClientRucAlreadyExistsException $e) {
            $errors[] = $e->getMessage();
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        }

        return $this->render('client/form.html.twig', [
            'errors' => $errors,
            'input'  => compact('name', 'ruc', 'address'),
        ], new Response(null, 422));
    }

    #[IsGranted('client.view')]
    #[Route('/{id}', name: 'client_show', methods: ['GET'])]
    public function showClient(
        string $id,
        FindClientByIdHandler $findClient,
        FindContactsByClientHandler $findContacts,
        FindUserByIdHandler $findUser,
        ListUsersHandler $listUsers,
        ListRolesHandler $listRoles,
        ListSitesByClientHandler $listSites,
        ListServiceZonesHandler $listZones,
    ): Response {
        try {
            $client   = $findClient(new FindClientByIdQuery($id));
            $contacts = $findContacts(new FindContactsByClientQuery($id));
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        }

        $rolesById = [];
        foreach ($listRoles(new ListRolesQuery()) as $role) {
            $rolesById[$role->id] = $role->name;
        }

        $linkedUsersByContactId = [];
        foreach ($contacts as $contact) {
            if ($contact->userId === null) {
                continue;
            }

            try {
                $linkedUsersByContactId[$contact->id] = $findUser(new FindUserByIdQuery($contact->userId));
            } catch (UserNotFoundException) {
                // usuario eliminado o no disponible
            }
        }

        return $this->render('client/show.html.twig', [
            'client'                 => $client,
            'contacts'               => $contacts,
            'linkedUsersByContactId' => $linkedUsersByContactId,
            'availableUsers'         => $listUsers(new ListUsersQuery()),
            'rolesById'              => $rolesById,
            'sites'                  => $listSites(new ListSitesByClientQuery($id)),
            'serviceZones'           => $listZones(new ListServiceZonesQuery(onlyActive: true)),
        ]);
    }

    #[Route('/{id}/edit', name: 'client_edit', methods: ['POST'])]
    public function editClient(
        string $id,
        Request $request,
        UpdateClientHandler $updateClient,
        FindClientByIdHandler $findClient,
        FindContactsByClientHandler $findContacts,
    ): Response {
        $name    = trim((string) $request->request->get('name', ''));
        $address = trim((string) $request->request->get('address', '')) ?: null;

        $errors = [];

        try {
            ($updateClient)(new UpdateClientCommand(
                clientId: $id,
                name:     $name,
                address:  $address,
            ));

            $this->addFlash('success', 'Datos del cliente actualizados.');

            return $this->redirectToRoute('client_show', ['id' => $id]);
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $client   = $findClient(new FindClientByIdQuery($id));
            $contacts = $findContacts(new FindContactsByClientQuery($id));
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        }

        return $this->render('client/show.html.twig', [
            'client'    => $client,
            'contacts'  => $contacts,
            'errors'    => $errors,
            'editMode'  => true,
            'editInput' => compact('name', 'address'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('client.activate')]
    #[Route('/{id}/activate', name: 'client_activate', methods: ['POST'])]
    public function activateClient(string $id, ActivateClientHandler $activateClient): Response
    {
        try {
            ($activateClient)(new ActivateClientCommand($id));
            $this->addFlash('success', 'Cliente activado.');
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $id]);
    }

    #[IsGranted('client.deactivate')]
    #[Route('/{id}/deactivate', name: 'client_deactivate', methods: ['POST'])]
    public function deactivateClient(string $id, DeactivateClientHandler $deactivateClient): Response
    {
        try {
            ($deactivateClient)(new DeactivateClientCommand($id));
            $this->addFlash('success', 'Cliente desactivado.');
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $id]);
    }

    #[IsGranted('client.delete')]
    #[Route('/{id}/delete', name: 'client_delete', methods: ['POST'])]
    public function deleteClient(string $id, DeleteClientHandler $deleteClient): Response
    {
        try {
            ($deleteClient)(new DeleteClientCommand($id));
            $this->addFlash('success', 'Cliente eliminado lógicamente.');
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->redirectToRoute('client_show', ['id' => $id]);
        }

        return $this->redirectToRoute('client_list');
    }
}
