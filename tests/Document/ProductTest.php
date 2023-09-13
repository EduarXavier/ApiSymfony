<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    private Product $product;

    public function testGetCode(): void
    {
        self::assertSame('', $this->product->getCode());
    }

    public function testSetCode(): void
    {
        $code = uniqid('Code', true);

        self::assertSame($this->product, $this->product->setCode($code));
        self::assertSame($code, $this->product->getCode());
    }

    public function testGetName(): void
    {
        self::assertSame('Juan', $this->product->getName());
    }

    public function testSetName(): void
    {
        $name = 'Juan';

        self::assertSame($this->product, $this->product->setName($name));
        self::assertSame($name, $this->product->getName());
    }

    public function testGetPrice(): void
    {
        self::assertSame(100, $this->product->getPrice());
    }

    public function testSetPrice(): void
    {
        $price = 100;

        self::assertSame($this->product, $this->product->setPrice($price));
        self::assertSame($price, $this->product->getPrice());
    }

    public function testGetAmount(): void
    {
        self::assertSame(10, $this->product->getAmount());
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
        $this->product = (new Product())
            ->setCode('')
            ->setName('Juan')
            ->setPrice(100)
            ->setAmount(10);
    }

    protected function tearDown(): void
    {
        unset($this->product);
    }
}