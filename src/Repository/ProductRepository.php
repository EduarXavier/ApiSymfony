<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Product;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

class ProductRepository extends ServiceDocumentRepository
{

    public function findAll(): array
    {
       return $this->findBy(['amount' => ['$gt' => 0], 'status' => Product::AVAILABLE], limit: 20);
    }

    public function findByName(string $name): ?Product
    {
        return $this->findOneBy(['name' => $name]) ?? null;
    }

    public function findByCode(string $code): ?Product
    {
        return $this->findOneBy(['code' => $code]) ?? null;
    }

    public function findExpiredProducts(): ?array
    {
        return $this->findBy(['$or' => [['status' => Product::EXPIRED], ['amount' => 0]]]) ?? null;
    }
}
