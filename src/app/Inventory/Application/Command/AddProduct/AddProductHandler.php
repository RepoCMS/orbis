<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AddProduct;

use App\Inventory\Application\Exception\ProductCodeAlreadyExistsException;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class AddProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(AddProductCommand $command): void
    {
        $code = new ProductCodeAssignedBySupplierValueObject($command->code);

        $existing = $this->productRepository->findAll();
        foreach ($existing as $p) {
            if ((string) $p->productCode() === (string) $code) {
                throw ProductCodeAlreadyExistsException::withCode($command->code);
            }
        }

        $product = Product::add(
            id:           new ProductIdValueObject($this->idGenerator->generate()),
            productCode:  $code,
            name:         new ProductNameValueObject($command->name),
            description:  $command->description,
            trackingType: $command->trackingType,
        );

        $this->productRepository->save($product);
    }
}
