<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Document\Product;
use App\Repository\ProductRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $productRepository;
    private Product $product;

    public function testFindAll(): void
    {
        $products = $this->productRepository->findAll();

        self::assertIsArray($products);
        self::assertContains($this->product, $products);
    }

    public function testFindByCode() : void
    {
        $find = $this->productRepository->findByCode($this->product->getCode());

        self::assertInstanceOf(Product::class, $find);
        self::assertEquals($this->product, $find);
        self::assertEquals($this->product->getCode(), $find->getCode());
    }

    public function testFindExpiredProducts() : void
    {
        $find = $this->productRepository->findExpiredProducts();

        self::assertIsArray($find);
        self::assertNotContains($this->product, $find);
    }


    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->productRepository = self::getContainer()->get(ProductRepository::class);
        $this->product = (new Product())
            ->setName('Jabon')
            ->setCode('650478611714d-Jabon')
            ->setAmount(10)
            ->setStatus(Product::AVAILABLE)
            ->setPrice(3000)
        ;
        $this->productRepository->getDocumentManager()->persist($this->product);
        $this->productRepository->getDocumentManager()->flush();
    }

    protected function tearDown(): void
    {
        $this->productRepository->getDocumentManager()->getSchemaManager()->dropDatabases();

        unset(
            $this->product,
            $this->productRepository
        );
    }

}