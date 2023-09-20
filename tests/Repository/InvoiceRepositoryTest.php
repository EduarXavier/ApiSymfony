<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Document\Invoice;
use App\Document\ProductInvoice;
use App\Document\User;
use App\Repository\InvoicesRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvoiceRepositoryTest extends KernelTestCase
{
    private InvoicesRepository $invoicesRepository;
    private DocumentManager $documentManager;
    private Invoice $invoice;
    private ProductInvoice $product;
    private User $user;

    public function testFindAllByUser(): void
    {
        $invoices = $this->invoicesRepository->findAllByUser($this->user);

        self::assertIsArray($invoices);
        self::assertContains($this->invoice,$invoices);
    }

    public function testFindNotCancelByUser(): void
    {
        $invoices = $this->invoicesRepository->findNotCancelByUser($this->user);

        self::assertIsArray($invoices);
        self::assertContains($this->invoice,$invoices);
    }

    public function testFindAllForStatus(): void
    {
        $invoices = $this->invoicesRepository->findAllForStatus($this->user, Invoice::INVOICE);
        $pay = $this->invoicesRepository->findAllForStatus($this->user, Invoice::PAY);

        self::assertIsArray($invoices);
        self::assertIsArray($pay);
        self::assertContains($this->invoice, $invoices);
        self::assertCount(0 ,$pay);
    }

    public function testFindByIdAndStatus(): void
    {
        $invoice = $this->invoicesRepository->findByIdAndStatus($this->invoice->getId(), Invoice::INVOICE);
        $pay = $this->invoicesRepository->findByIdAndStatus($this->invoice->getId(), Invoice::PAY);

        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertEquals($invoice, $this->invoice);
        self::assertNull($pay);
    }

    public function testFindByCode(): void
    {
        $invoice = $this->invoicesRepository->findByCode($this->invoice->getCode());

        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertEquals($invoice, $this->invoice);
    }

    public function testFindByUserAndStatus(): void
    {
        $invoice = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::INVOICE);
        $cancel = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::CANCEL);

        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertEquals($invoice, $this->invoice);
        self::assertNull($cancel);
    }

    public function testFindByProduct(): void
    {
        $invoice = $this->invoicesRepository->findByProduct($this->product);

        self::assertIsArray($invoice);
        self::assertContains($this->invoice, $invoice);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->invoicesRepository = self::getContainer()->get(InvoicesRepository::class);
        $this->documentManager = $this->invoicesRepository->getDocumentManager();
        $this->user = (new User())
            ->setName('userTest')
        ;
        $this->product = (new ProductInvoice())
            ->setName('Jabon')
            ->setCode('65047fae8ff348-Jabon');
        $this->documentManager->persist($this->user);
        $this->documentManager->flush();
        $this->invoice = (new Invoice())
            ->setUser($this->user)
            ->setCode('65047fae8ff28-1094045112')
            ->setDate(new DateTime('today', new DateTimeZone('America/Bogota')))
            ->setStatus(Invoice::INVOICE)
            ->addProduct($this->product)
        ;
        $this->documentManager->persist($this->invoice);
        $this->documentManager->flush();
    }

    protected function tearDown(): void
    {
        $this->documentManager->getSchemaManager()->dropDatabases();

        unset(
            $this->product,
            $this->invoice,
            $this->user,
            $this->invoicesRepository,
            $this->documentManager
        );
    }

}
