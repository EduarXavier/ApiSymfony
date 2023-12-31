<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\Invoice;
use App\Document\ProductInvoice;
use App\Document\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    private Invoice $invoice;

    /**
     * @throws Exception
     */
    public function testGetId(): void
    {
        $id = uniqid();
        $this->invoice = $this->createConfiguredMock(Invoice::class, [
            "getId" => $id
        ]);

        self::assertSame($id, $this->invoice->getId());
    }

    public function testGetCode(): void
    {
        $this->invoice->setCode('64fb330a54e93-Galleta-verde');

        self::assertSame('64fb330a54e93-Galleta-verde', $this->invoice->getCode());
    }

    public function testSetCode(): void
    {
        $code = uniqid('Code', true);

        self::assertSame($this->invoice, $this->invoice->setCode($code));
        self::assertSame($code, $this->invoice->getCode());
    }

    public function testGetProducts(): void
    {
        self::assertInstanceOf(ArrayCollection::class, $this->invoice->getProducts());
    }

    public function testGetDate(): void
    {
        $date = new DateTime('today');
        $this->invoice->setDate($date);

        self::assertEquals($date, $this->invoice->getDate());
    }

    public function testSetDate(): void
    {
        $date = (new DateTime('tomorrow'));

        self::assertSame($this->invoice, $this->invoice->setDate($date));
        self::assertEquals($date, $this->invoice->getDate());
    }

    public function testAddProducts(): void
    {
        $product = (new ProductInvoice())->setName('Jabon');

        self::assertSame($this->invoice, $this->invoice->addProduct($product));
        self::assertTrue($this->invoice->getProducts()->contains($product));
    }

    public function testRemoveProduct(): void
    {
        $product = (new ProductInvoice())->setName('Jabon');
        $this->invoice->addProduct($product);

        self::assertTrue($this->invoice->removeProduct($product));
        self::assertNotContains($product, $this->invoice->getProducts());
    }

    public function testGetUser(): void
    {
        $user = (new User())->setName('Juan');
        $this->invoice->setUser($user);

        self::assertSame($user, $this->invoice->getUser());
    }

    public function testSetUser(): void
    {
        $user = (new User())->setName('Juan');

        self::assertSame($this->invoice, $this->invoice->setUser($user));
        self::assertSame($user, $this->invoice->getUser());
    }

    public function testGetStatus(): void
    {
        $status = 'shopping-cart';
        $this->invoice->setStatus($status);

        self::assertSame($status, $this->invoice->getStatus());
    }

    public function testSetStatus(): void
    {
        $status = 'shopping-cart';

        self::assertSame($this->invoice, $this->invoice->setStatus($status));
        self::assertSame($status, $this->invoice->getStatus());
    }

    protected function setUp(): void
    {
        //parent::setUp();
        $this->invoice = (new Invoice());
    }

    protected function tearDown(): void
    {
        unset($this->invoice);
    }
}