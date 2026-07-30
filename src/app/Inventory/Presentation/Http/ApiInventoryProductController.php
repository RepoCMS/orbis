<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\AddProduct\AddProductCommand;
use App\Inventory\Application\Command\AddProduct\AddProductHandler;
use App\Inventory\Application\Command\DeleteProduct\DeleteProductCommand;
use App\Inventory\Application\Command\DeleteProduct\DeleteProductHandler;
use App\Inventory\Application\Command\UpdateProduct\UpdateProductCommand;
use App\Inventory\Application\Command\UpdateProduct\UpdateProductHandler;
use App\Inventory\Application\Exception\ProductCodeAlreadyExistsException;
use App\Inventory\Application\Exception\ProductHasStockException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Query\FindProductById\FindProductByIdHandler;
use App\Inventory\Application\Query\ListProducts\ListProductsHandler;
use App\Inventory\Application\Query\StockByProduct\StockByProductHandler;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory')]
final class ApiInventoryProductController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('/products', name: 'api_inventory_products_list', methods: ['GET'])]
    public function listProducts(
        Request $request,
        ListProductsHandler $handler,
    ): JsonResponse {
        $products = $handler();

        $trackingType = $request->query->get('trackingType');
        $q            = $request->query->get('q');

        if ($trackingType !== null) {
            $products = array_values(array_filter(
                $products,
                static fn($p) => $p->trackingType === $trackingType,
            ));
        }

        if ($q !== null) {
            $search = mb_strtolower($q);
            $products = array_values(array_filter(
                $products,
                static fn($p) => str_contains(mb_strtolower($p->name), $search)
                    || str_contains(mb_strtolower($p->code), $search),
            ));
        }

        return $this->json(['data' => $products]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/products', name: 'api_inventory_products_create', methods: ['POST'])]
    public function createProduct(
        Request $request,
        AddProductHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new AddProductCommand(
                code:         trim((string) ($payload['code'] ?? '')),
                name:         trim((string) ($payload['name'] ?? '')),
                description:  trim((string) ($payload['description'] ?? '')),
                trackingType: trim((string) ($payload['trackingType'] ?? 'serialized')),
            ));

            return $this->json(
                ['message' => 'Producto creado exitosamente.'],
                Response::HTTP_CREATED,
            );
        } catch (ProductCodeAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/products/{id}', name: 'api_inventory_products_show', methods: ['GET'])]
    public function showProduct(
        string $id,
        FindProductByIdHandler $findProduct,
        StockByProductHandler $stockByProduct,
    ): JsonResponse {
        try {
            $product = $findProduct($id);
            $stock   = $stockByProduct($id);

            return $this->json([
                'data' => [
                    'product'            => $product,
                    'serializedStock'    => $stock['serialized'],
                    'nonserializedStock' => $stock['nonserialized'],
                ],
            ]);
        } catch (ProductNotFoundException) {
            return $this->json(['error' => 'Producto no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/products/{id}', name: 'api_inventory_products_update', methods: ['PATCH'])]
    public function updateProduct(
        string $id,
        Request $request,
        UpdateProductHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        $trackingType = isset($payload['trackingType'])
            ? trim((string) $payload['trackingType'])
            : null;

        try {
            ($handler)(new UpdateProductCommand(
                id:           $id,
                name:         trim((string) ($payload['name'] ?? '')),
                description:  trim((string) ($payload['description'] ?? '')),
                trackingType: $trackingType,
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (ProductNotFoundException) {
            return $this->json(['error' => 'Producto no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (ProductHasStockException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/products/{id}/tracking-type', name: 'api_inventory_products_change_tracking_type', methods: ['PATCH'])]
    public function changeTrackingType(
        string $id,
        Request $request,
        FindProductByIdHandler $findProduct,
        UpdateProductHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $trackingType = trim((string) ($payload['trackingType'] ?? ''));

        if ($trackingType === '') {
            return $this->json(
                ['error' => 'El campo trackingType es obligatorio.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $product = $findProduct($id);

            ($handler)(new UpdateProductCommand(
                id:           $id,
                name:         $product->name,
                description:  $product->description,
                trackingType: $trackingType,
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (ProductNotFoundException) {
            return $this->json(['error' => 'Producto no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (ProductHasStockException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.delete')]
    #[Route('/products/{id}', name: 'api_inventory_products_delete', methods: ['DELETE'])]
    public function deleteProduct(
        string $id,
        DeleteProductHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new DeleteProductCommand($id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (ProductNotFoundException) {
            return $this->json(['error' => 'Producto no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/products/{id}/stock', name: 'api_inventory_products_stock', methods: ['GET'])]
    public function stockByProduct(
        string $id,
        FindProductByIdHandler $findProduct,
        StockByProductHandler $handler,
    ): JsonResponse {
        try {
            $product = $findProduct($id);
            $stock   = $handler($id);

            return $this->json([
                'data' => [
                    'product'       => $product,
                    'serialized'    => $stock['serialized'],
                    'nonserialized' => $stock['nonserialized'],
                ],
            ]);
        } catch (ProductNotFoundException) {
            return $this->json(['error' => 'Producto no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }
}
