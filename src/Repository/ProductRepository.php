<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Product;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

class ProductRepository extends ServiceDocumentRepository
{

    public function findAll(): array
    {
       return $this->findBy(["amount" => ['$gt' => 0]], limit: 20);
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function findById(string $id): ?Product
    {
        return $this->find($id);
    }

    public function findByCode(string $code): ?Product
    {
        return $this->findOneBy(["code" => $code]) ?? null;
    }
}
