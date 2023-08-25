<?php

namespace App\Repository;

use App\Document\Products;
use Doctrine\ODM\MongoDB\DocumentManager;

class ProductRepository implements ProductRepositoryInterface
{

    public function findAll(DocumentManager $documentManager): ?array
    {
        $repository = $documentManager->getRepository(Products::class);
        $products = $repository->findAll();

        return $products;
    }

    public function findByIf(string $id, DocumentManager $documentManager): ?Products
    {
        // TODO: Implement findByIf() method.
    }

    public function addProduct(Products $product, DocumentManager $documentManager): ?Products
    {
        // TODO: Implement addProduct() method.
    }

    public function updateProduct(Products $product, DocumentManager $documentManager): ?Products
    {
        // TODO: Implement updateProduct() method.
    }

    public function deleteProduct(string $id, DocumentManager $documentManager): ?Products
    {
        // TODO: Implement deleteProduct() method.
    }
}