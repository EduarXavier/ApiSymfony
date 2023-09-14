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
       return $this->findBy(["amount" => ['$gt' => 0], 'status' => 'available'], limit: 20);
    }

    public function findByName(string $name): ?Product
    {
        return $this->findOneBy(["name" => $name]) ?? null;
    }

    public function findByCode(string $code): ?Product
    {
        return $this->findOneBy(["code" => $code]) ?? null;
    }

    public function findExpiredProducts(): ?array
    {
        return $this->findBy(['$or' => [['status' => 'expired'], ['amount' => 0]]]) ?? null;
    }
}
