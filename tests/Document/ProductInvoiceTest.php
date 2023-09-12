<?php

namespace App\Tests\Document;

use App\Document\Product;
use App\Document\ProductInvoice;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ProductInvoiceTest extends TestCase
{
    private ProductInvoice $productInvoice;

    public function testGetCode(): void
    {
        self::assertSame('', $this->productInvoice->getCode());
    }

    public function testSetCode(): void
    {
        $code = uniqid('Code', true);

        self::assertSame($this->productInvoice, $this->productInvoice->setCode($code));
        self::assertSame($code, $this->productInvoice->getCode());
    }

    public function testGetName(): void
    {
        self::assertSame('Juan', $this->productInvoice->getName());
    }

    public function testSetName(): void
    {
        $name = 'Juan';

        self::assertSame($this->productInvoice, $this->productInvoice->setName($name));
        self::assertSame($name, $this->productInvoice->getName());
    }

    public function testGetPrice(): void
    {
        self::assertSame(100, $this->productInvoice->getPrice());
    }

    public function testSetPrice(): void
    {
        $price = 100;

        self::assertSame($this->productInvoice, $this->productInvoice->setPrice($price));
        self::assertSame($price, $this->productInvoice->getPrice());
    }

    public function testGetAmount(): void
    {
        self::assertSame(10, $this->productInvoice->getAmount());
    }

    public function testSetAmount(): void
    {
        $amount = 10;

        self::assertSame($this->productInvoice, $this->productInvoice->setAmount($amount));
        self::assertSame($amount, $this->productInvoice->getAmount());
    }

    protected function setUp(): void
    {
        //parent::setUp();
        $this->productInvoice = (new ProductInvoice())
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