<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\AddEntryItem\AddEntryItemCommand;
use App\Inventory\Application\Command\AddEntryItem\AddEntryItemHandler;
use App\Inventory\Application\Command\CancelEntry\CancelEntryCommand;
use App\Inventory\Application\Command\CancelEntry\CancelEntryHandler;
use App\Inventory\Application\Command\ConfirmEntry\ConfirmEntryCommand;
use App\Inventory\Application\Command\ConfirmEntry\ConfirmEntryHandler;
use App\Inventory\Application\Command\CreateEntry\CreateEntryCommand;
use App\Inventory\Application\Command\CreateEntry\CreateEntryHandler;
use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Application\Query\FindEntryById\FindEntryByIdHandler;
use App\Inventory\Application\Query\ListEntries\ListEntriesHandler;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListProducts\ListProductsHandler;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/entries')]
final class EntryController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_entry_list', methods: ['GET'])]
    public function list(ListEntriesHandler $handler): Response
    {
        return $this->render('inventory/entry/list.html.twig', [
            'entries' => $handler(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_entry_new', methods: ['GET'])]
    public function newForm(): Response
    {
        return $this->render('inventory/entry/form.html.twig', [
            'errors' => [],
            'input'  => [],
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_entry_create', methods: ['POST'])]
    public function create(Request $request, CreateEntryHandler $handler): Response
    {
        $reference = trim((string) $request->request->get('reference', ''));
        $notes     = trim((string) $request->request->get('notes', '')) ?: null;
        $userId    = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new CreateEntryCommand($reference, $notes, $userId));
            $this->addFlash('success', sprintf('Entrada "%s" creada.', $reference));

            return $this->redirectToRoute('inventory_entry_list');
        } catch (DomainException $e) {
            return $this->render('inventory/entry/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input'  => compact('reference', 'notes'),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_entry_show', methods: ['GET'])]
    public function show(
        string $id,
        FindEntryByIdHandler $findEntry,
        ListProductsHandler $listProducts,
        ListLocationsHandler $listLocations,
    ): Response {
        try {
            $data = $findEntry($id);
        } catch (EntryNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/entry/show.html.twig', [
            'entry'     => $data['entry'],
            'items'     => $data['items'],
            'products'  => $listProducts(),
            'locations' => $listLocations(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/{id}/items', name: 'inventory_entry_add_item', methods: ['POST'])]
    public function addItem(string $id, Request $request, AddEntryItemHandler $handler): Response
    {
        $productId    = trim((string) $request->request->get('product_id', ''));
        $locationId   = trim((string) $request->request->get('location_id', ''));
        $serialNumber = trim((string) $request->request->get('serial_number', '')) ?: null;
        $quantity     = (int) $request->request->get('quantity', 1);
        $poItemId     = trim((string) $request->request->get('po_item_id', '')) ?: null;

        try {
            ($handler)(new AddEntryItemCommand(
                entryId:             $id,
                productId:           $productId,
                locationId:          $locationId,
                serialNumber:        $serialNumber,
                quantity:            $quantity,
                purchaseOrderItemId: $poItemId,
            ));
            $this->addFlash('success', 'Ítem agregado a la entrada.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_entry_show', ['id' => $id]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/{id}/confirm', name: 'inventory_entry_confirm', methods: ['POST'])]
    public function confirm(string $id, ConfirmEntryHandler $handler): Response
    {
        try {
            ($handler)(new ConfirmEntryCommand($id));
            $this->addFlash('success', 'Entrada confirmada. El stock ha sido registrado.');
        } catch (EntryNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_entry_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/cancel', name: 'inventory_entry_cancel', methods: ['POST'])]
    public function cancel(string $id, CancelEntryHandler $handler): Response
    {
        try {
            ($handler)(new CancelEntryCommand($id));
            $this->addFlash('success', 'Entrada cancelada.');
        } catch (EntryNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_entry_list');
    }
}
