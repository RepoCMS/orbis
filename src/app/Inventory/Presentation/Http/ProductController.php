<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\AddProduct\AddProductCommand;
use App\Inventory\Application\Command\AddProduct\AddProductHandler;
use App\Inventory\Application\Command\DeleteProduct\DeleteProductCommand;
use App\Inventory\Application\Command\DeleteProduct\DeleteProductHandler;
use App\Inventory\Application\Command\RegisterDirectStock\RegisterDirectStockCommand;
use App\Inventory\Application\Command\RegisterDirectStock\RegisterDirectStockHandler;
use App\Inventory\Application\Command\UpdateProduct\UpdateProductCommand;
use App\Inventory\Application\Command\UpdateProduct\UpdateProductHandler;
use App\Inventory\Application\Command\WriteOffNonserializedStock\WriteOffNonserializedStockCommand;
use App\Inventory\Application\Command\WriteOffNonserializedStock\WriteOffNonserializedStockHandler;
use App\Inventory\Application\Exception\ProductHasStockException;
use App\Inventory\Application\Exception\StockNotFoundException;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\ProductCodeAlreadyExistsException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Query\FindProductById\FindProductByIdHandler;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListProducts\ListProductsHandler;
use App\Inventory\Application\Query\StockByProduct\StockByProductHandler;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/products')]
final class ProductController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_product_list', methods: ['GET'])]
    public function list(ListProductsHandler $handler): Response
    {
        return $this->render('inventory/product/list.html.twig', [
            'products' => $handler(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_product_new', methods: ['GET'])]
    public function newForm(): Response
    {
        return $this->render('inventory/product/form.html.twig', [
            'errors' => [],
            'input'  => [],
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_product_create', methods: ['POST'])]
    public function create(Request $request, AddProductHandler $handler): Response
    {
        $code         = trim((string) $request->request->get('code', ''));
        $name         = trim((string) $request->request->get('name', ''));
        $description  = trim((string) $request->request->get('description', ''));
        $trackingType = trim((string) $request->request->get('tracking_type', 'serialized'));

        try {
            ($handler)(new AddProductCommand($code, $name, $description, $trackingType));
            $this->addFlash('success', sprintf('Producto "%s" registrado.', $name));

            return $this->redirectToRoute('inventory_product_list');
        } catch (ProductCodeAlreadyExistsException | DomainException $e) {
            return $this->render('inventory/product/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input'  => compact('code', 'name', 'description', 'trackingType'),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_product_show', methods: ['GET'])]
    public function show(
        string $id,
        FindProductByIdHandler $findProduct,
        StockByProductHandler $stockByProduct,
        ListLocationsHandler $listLocations,
    ): Response {
        try {
            $product = $findProduct($id);
            $stock   = $stockByProduct($id);
        } catch (ProductNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/product/show.html.twig', [
            'product'             => $product,
            'serializedStock'     => $stock['serialized'],
            'nonserializedStock'  => $stock['nonserialized'],
            'locations'           => $listLocations(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/{id}/stock', name: 'inventory_product_register_stock', methods: ['POST'])]
    public function registerStock(
        string $id,
        Request $request,
        RegisterDirectStockHandler $handler,
    ): Response {
        $locationId   = trim((string) $request->request->get('location_id', ''));
        $serialNumber = trim((string) $request->request->get('serial_number', '')) ?: null;
        $quantity     = max(1, (int) $request->request->get('quantity', 1));

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            ($handler)(new RegisterDirectStockCommand(
                productId:      $id,
                locationId:     $locationId,
                serialNumber:   $serialNumber,
                quantity:       $quantity,
                registeredById: (string) $user->getDomainUser()->id(),
            ));

            $label = $serialNumber !== null
                ? sprintf('Stock serializado "%s" registrado.', $serialNumber)
                : sprintf('%d unidades cargadas al stock.', $quantity);

            $this->addFlash('success', $label);
        } catch (ProductNotFoundException | LocationNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Error al registrar el stock: ' . $e->getMessage());
        }

        return $this->redirectToRoute('inventory_product_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/edit', name: 'inventory_product_edit', methods: ['GET'])]
    public function editForm(
        string $id,
        FindProductByIdHandler $findProduct,
        StockByProductHandler $stockByProduct,
    ): Response {
        try {
            $product = $findProduct($id);
            $stock   = $stockByProduct($id);
        } catch (ProductNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $hasStock = !empty($stock['serialized']) || !empty($stock['nonserialized']);

        return $this->render('inventory/product/form.html.twig', [
            'product'  => $product,
            'hasStock' => $hasStock,
            'errors'   => [],
            'input'    => [
                'code'          => $product->code,
                'name'          => $product->name,
                'description'   => $product->description,
                'tracking_type' => $product->trackingType,
            ],
        ]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}', name: 'inventory_product_update', methods: ['POST', 'PUT'])]
    public function update(
        string $id,
        Request $request,
        UpdateProductHandler $handler,
        StockByProductHandler $stockByProduct,
        FindProductByIdHandler $findProduct,
    ): Response {
        $name         = trim((string) $request->request->get('name', ''));
        $description  = trim((string) $request->request->get('description', ''));
        $trackingType = $request->request->has('tracking_type')
            ? trim((string) $request->request->get('tracking_type'))
            : null;

        try {
            ($handler)(new UpdateProductCommand($id, $name, $description, $trackingType));
            $this->addFlash('success', sprintf('Producto "%s" actualizado.', $name));

            return $this->redirectToRoute('inventory_product_show', ['id' => $id]);
        } catch (ProductNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (ProductHasStockException $e) {
            try {
                $product  = $findProduct($id);
                $stock    = $stockByProduct($id);
                $hasStock = !empty($stock['serialized']) || !empty($stock['nonserialized']);
            } catch (ProductNotFoundException) {
                throw $this->createNotFoundException();
            }

            return $this->render('inventory/product/form.html.twig', [
                'product'  => $product,
                'hasStock' => $hasStock,
                'errors'   => [$e->getMessage()],
                'input'    => ['code' => $product->code, 'name' => $name, 'description' => $description, 'tracking_type' => $trackingType],
            ], new Response(null, 422));
        } catch (DomainException $e) {
            return $this->render('inventory/product/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input'  => compact('name', 'description'),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.delete')]
    #[Route('/{id}/delete', name: 'inventory_product_delete', methods: ['POST'])]
    public function delete(string $id, DeleteProductHandler $handler): Response
    {
        try {
            ($handler)(new DeleteProductCommand($id));
            $this->addFlash('success', 'Producto eliminado.');
        } catch (ProductNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_product_list');
    }

    #[IsGranted('inventory.update')]
    #[Route('/{productId}/stock/{stockId}/write-off', name: 'inventory_stock_write_off', methods: ['POST'])]
    public function writeOff(
        string $productId,
        string $stockId,
        Request $request,
        WriteOffNonserializedStockHandler $handler,
    ): Response {
        $action = $request->request->get('action', 'damage');

        try {
            ($handler)(new WriteOffNonserializedStockCommand($stockId, $action));
            $this->addFlash('success', 'Baja de stock registrada.');
        } catch (StockNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_product_show', ['id' => $productId]);
    }
}