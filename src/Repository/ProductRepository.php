<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Product;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;

class ProductRepository extends ServiceDocumentRepository
{
    public function findAll(): array
    {
        $repository = $this->getDocumentManager()->getRepository(Product::class);

        return $repository->findBy(["amount" => ['$gt' => 0]], limit: 20);
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function findById(string $id): ?Product
    {
        return $this->getDocumentManager()->getRepository(Product::class)->find($id);
    }

    public function findByCode(string $code): ?Product
    {
        $repository = $this->getDocumentManager()->getRepository(Product::class);

        return $repository->findOneBy(["code" => $code]) ?? null;
    }

    /**
     * @throws MongoDBException
     */
    public function addProduct(Product $product): ?string
    {
        $product->setCode(password_hash($product->getName(), PASSWORD_BCRYPT));
        $this->getDocumentManager()->persist($product);
        $this->getDocumentManager()->flush();

        return $product->getId();
    }

    /**
     * @throws MongoDBException
     */
    public function updateProduct(Product $product): ?string
    {
        $productUpdate = $this->findByCode($product->getCode());
        $productUpdate->setAmount($product->getAmount());
        $this->getDocumentManager()->persist($productUpdate);
        $this->getDocumentManager()->flush();

        return $product->getId();
    }

    /**
     * @throws MongoDBException
     */
    public function deleteProduct(Product $product): ?bool
    {
        $this->getDocumentManager()->remove($product);
        $this->getDocumentManager()->flush();

        return true;
    }
}
