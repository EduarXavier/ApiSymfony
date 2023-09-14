<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\Product;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    private Product $product;

    /**
     * @throws Exception
     */
    public function testGetId(): void
    {
        $id = uniqid();
        $this->product = $this->createConfiguredMock(Product::class, [
            "getId" => $id
        ]);

        self::assertSame($id, $this->product->getId());
    }

    public function testGetCode(): void
    {
        $code = '64fb330a54e93-Galleta-verde';
        $this->product->setCode($code);

        self::assertSame($code, $this->product->getCode());
    }

    public function testSetCode(): void
    {
        $code = uniqid('Code', true);

        self::assertSame($this->product, $this->product->setCode($code));
        self::assertSame($code, $this->product->getCode());
    }

    public function testGetName(): void
    {
        $name = 'Juan';
        $this->product->setName($name);

        self::assertSame($name, $this->product->getName());
    }

    public function testSetName(): void
    {
        $name = 'Juan';

        self::assertSame($this->product, $this->product->setName($name));
        self::assertSame($name, $this->product->getName());
    }

    public function testGetPrice(): void
    {
        $price = 100;
        $this->product->setPrice($price);

        self::assertSame($price, $this->product->getPrice());
    }

    public function testSetPrice(): void
    {
        $price = 100;

        self::assertSame($this->product, $this->product->setPrice($price));
        self::assertSame($price, $this->product->getPrice());
    }

    public function testGetAmount(): void
    {
        $amount = 10;
        $this->product->setAmount($amount);

        self::assertSame($amount, $this->product->getAmount());
    }

    public function testSetAmount(): void
    {
        $amount = 10;

        self::assertSame($this->product, $this->product->setAmount($amount));
        self::assertSame($amount, $this->product->getAmount());
    }

    protected function setUp(): void
    {
        //parent::setUp();
        $this->product = new Product();
    }

    protected function tearDown(): void
    {
        unset($this->product);
    }
}