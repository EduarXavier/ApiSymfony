<?php

namespace App\Repository;

use App\Document\Invoice;
use App\Document\Product;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;

class ProductRepository implements ProductRepositoryInterface
{
    public function findAll(DocumentManager $documentManager): ?array
    {
        $repository = $documentManager->getRepository(Product::class);

        return $repository->findBy(["amount" => ['$gt' => 0]], limit: 20);
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function findById(string $id, DocumentManager $documentManager): ?Product
    {
        return $documentManager->getRepository(Product::class)->find($id);
    }

    /**
     * @throws MongoDBException
     */
    public function addProduct(Product $product, DocumentManager $documentManager): ?string
    {
        $documentManager->persist($product);
        $documentManager->flush();

        return $product->getId();
    }

    /**
     * @throws MongoDBException
     */
    public function updateProduct(Product $product, DocumentManager $documentManager): ?string
    {
        $documentManager->flush();

        return $product->getId();
    }

    /**
     * @throws MongoDBException
     */
    public function deleteProduct(Product $product, DocumentManager $documentManager): ?bool
    {
        $documentManager->remove($product);
        $documentManager->flush();

        return true;
    }
}
