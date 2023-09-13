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
}
