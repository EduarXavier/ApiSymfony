<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Product;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

class ProductRepository extends ServiceDocumentRepository
{
    public const CANT_MAX_PRODUCTS = 12;

    public function findAll(): array
    {
       return $this->findBy(['amount' => ['$gt' => 0], 'status' => Product::AVAILABLE], ['name' => 1]);
    }

    public function findAllPaginator(int $offset = 0): array
    {
        return $this->findBy(
            [
                'amount' => ['$gt' => 0],
                'status' => Product::AVAILABLE
            ],
            ['name' => 1],
            self::CANT_MAX_PRODUCTS,
            $offset
        );
    }

    public function findByCode(string $code): ?Product
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findExpiredProducts(int $offset = 0): array
    {
        return $this->findBy(
            ['$or' => [
                ['status' => Product::EXPIRED],
                ['amount' => 0]
            ]],
            ['name' => 1],
            self::CANT_MAX_PRODUCTS,
            $offset
        );
    }

    public function countExpiredProducts(): int
    {
        return count($this->findBy(['$or' => [['status' => Product::EXPIRED], ['amount' => 0]]]));
    }
}
