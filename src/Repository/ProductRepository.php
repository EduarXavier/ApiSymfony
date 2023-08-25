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
        $products = $repository->findBy(["amount" => ['$gt' => 0]]);

        return $products;
    }

    public function findById(string $id, DocumentManager $documentManager): ?Product
    {
        $product = $documentManager->getRepository(Product::class)->find($id);

        return $product;
    }

    public function addProduct(Product $product, DocumentManager $documentManager): ?Product
    {

    }

    public function updateProduct(Product $product, DocumentManager $documentManager): ?Product
    {
        $products = $documentManager
            ->getRepository(Product::class)
            ->findBy(
                [
                    '_id' => $product->getId(),
                    'amount' => ['$gt' => 0]
                ]);

        $product = $products[0];
        $product->setAmount($product->getAmount());

        return $product;
    }

    public function deleteProduct(string $id, DocumentManager $documentManager): ?Product
    {
        // TODO: Implement deleteProduct() method.
    }
}