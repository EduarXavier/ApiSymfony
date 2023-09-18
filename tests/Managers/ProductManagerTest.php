<?php

declare(strict_types=1);

namespace App\Tests\Managers;

use App\Document\Product;
use App\Managers\ProductManager;
use App\Repository\ProductRepository;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductManagerTest extends KernelTestCase
{
    private ProductManager $productManager;
    private ProductRepository $productRepository;
    private Product $product;

    /**
     * @throws MongoDBException
     */
    public function testAddProduct(): void
    {
        $this->productManager->addProduct($this->product);
        $this->productRepository->getDocumentManager()->flush();
        $productFind = $this->productRepository->findByCode($this->product->getCode());

        self::assertInstanceOf(Product::class, $productFind);
        self::assertSame($this->product, $productFind);
    }

    /**
     * @throws MongoDBException
     */
    public function testUpdateProduct(): void
    {
        $amount = 100;
        $this->productManager->addProduct($this->product);
        $this->productRepository->getDocumentManager()->flush();
        $this->product->setAmount($amount);
        $this->productManager->updateProduct($this->product);
        $this->productRepository->getDocumentManager()->flush();
        $productFind = $this->productRepository->findByCode($this->product->getCode());

        self::assertInstanceOf(Product::class, $productFind);
        self::assertEquals($amount, $productFind->getAmount());
        self::assertSame($this->product, $productFind);
    }

    /**
     * @throws MongoDBException
     */
    public function testDeleteProduct(): void
    {
        $this->productManager->addProduct($this->product);
        $this->productRepository->getDocumentManager()->flush();
        $this->productManager->deleteProduct($this->product);
        $this->productRepository->getDocumentManager()->flush();
        $productFind = $this->productRepository->findByCode($this->product->getCode());

        self::assertNull($productFind);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->productRepository = $container->get(ProductRepository::class);
        $this->productManager = $container->get(ProductManager::class);
        $this->product = (new Product())
            ->setName('Jabon')
            ->setCode('650478611714d-Jabon')
            ->setAmount(10)
            ->setStatus(Product::AVAILABLE)
            ->setPrice(3000)
        ;
    }

    protected function tearDown(): void
    {
        $this->productRepository->getDocumentManager()->getSchemaManager()->dropDatabases();

        unset(
            $this->product,
            $this->productRepository,
            $this->productManager
        );
    }
}