<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Product;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceDocumentRepository
{
    private DocumentManager $documentManager;
    public function __construct(ManagerRegistry $registry, $documentClass)
    {
        parent::__construct($registry, $documentClass);
        $this->documentManager = $this->getDocumentManager();
    }

    public function findAll(): array
    {
        $repository = $this->documentManager->getRepository(Product::class);

        return $repository->findBy(["amount" => ['$gt' => 0]], limit: 20);
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function findById(string $id): ?Product
    {
        return $this->documentManager->getRepository(Product::class)->find($id);
    }

    public function findByCode(string $code): ?Product
    {
        $repository = $this->documentManager->getRepository(Product::class);

        return $repository->findOneBy(["code" => $code]) ?? null;
    }

    /**
     * @throws MongoDBException
     */
    public function addProduct(Product $product): ?string
    {
        $product->setCode(str_ireplace(" ", "-", uniqid(). "-" . $product->getName()));
        $this->documentManager->persist($product);
        $this->documentManager->flush();

        return $product->getCode();
    }

    /**
     * @throws MongoDBException
     */
    public function updateProduct(Product $product): ?string
    {
        $productUpdate = $this->findByCode($product->getCode());
        $productUpdate->setAmount($product->getAmount());
        $this->documentManager->persist($productUpdate);
        $this->documentManager->flush();
        $this->documentManager->clear();

        return $product->getId();
    }

    /**
     * @throws MongoDBException
     */
    public function deleteProduct(Product $product): ?bool
    {
        $this->documentManager->remove($product);
        $this->documentManager->flush();

        return true;
    }
}
