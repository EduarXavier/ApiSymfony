<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Product;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

class ProductRepository extends ServiceDocumentRepository
{

    public function findAll(): array
    {
       return $this->findBy(['amount' => ['$gt' => 0], 'status' => Product::AVAILABLE], ['name' => 1]);
    }

    public function findByCode(string $code): ?Product
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findExpiredProducts(): array
    {
        return $this->findBy(['$or' => [['status' => Product::EXPIRED], ['amount' => 0]]]) ;
    }
}
