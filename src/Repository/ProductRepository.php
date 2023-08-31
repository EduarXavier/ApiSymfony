<?php

namespace App\Repository;

use App\Document\Invoice;
use App\Document\Product;
use Doctrine\ODM\MongoDB\DocumentManager;

class ProductRepository implements ProductRepositoryInterface
{

    public function findAll(DocumentManager $documentManager): ?array
    {
        $repository = $documentManager->getRepository(Product::class);
        $products = $repository->findBy(["amount" => ['$gt' => 0]], limit: 20);

        return $products;
    }

    public function findById(string $id, DocumentManager $documentManager): ?Product
    {
        $product = $documentManager->getRepository(Product::class)->find($id);

        return $product;
    }

    public function addProduct(Product $product, DocumentManager $documentManager): ?string
    {
        $documentManager->persist($product);
        $documentManager->flush();

        return $product->getId();
    }

    public function updateProduct(Product $product, DocumentManager $documentManager): ?string
    {
        $documentManager->flush();

        return $product->getId();
    }

    public function deleteProduct(Product $product, DocumentManager $documentManager): ?bool
    {
        $documentManager->remove($product);
        $documentManager->flush();

        return true;
    }
}
