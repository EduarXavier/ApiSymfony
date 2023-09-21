<?php

namespace App\Tests\Managers;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\ProductInvoice;
use App\Document\User;
use App\Managers\InvoiceManager;
use App\Repository\InvoicesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvoiceManagerTest extends KernelTestCase
{
    private InvoicesRepository $invoicesRepository;
    private InvoiceManager $invoiceManager;
    private User $user;
    private ArrayCollection $products;

    /**
     * @throws MongoDBException
     */
    public function testCreateNewCart(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);

        self::assertInstanceOf(Invoice::class, $invoiceFind);
        self::assertIsArray($invoiceFind->getProducts()->toArray());
    }

    /**
     * @throws MongoDBException
     */
    public function testInvoiceResume(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $productsInvoices = $this->invoiceManager->invoiceResume($this->user, null);

        self::assertInstanceOf(Collection::class, $productsInvoices);
        self::assertCount(1, $productsInvoices);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function testAddToExistingCart(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);
        $this->products->clear();
        $productInvoice = (new ProductInvoice())
            ->setName('Jabon')
            ->setCode('65047fae8ff348-Jabon')
            ->setAmount(2)
        ;
        $this->products->add($productInvoice);
        $this->invoiceManager->addToExistingCart($this->products, $invoiceFind);
        $this->invoicesRepository->getDocumentManager()->flush();

        self::assertInstanceOf(Invoice::class, $invoiceFind);
        self::assertIsArray($invoiceFind->getProducts()->toArray());
        self::assertCount(1, $invoiceFind->getProducts());
    }

    /**
     * @throws MongoDBException
     */
    public function testCreateInvoice(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);
        $this->invoiceManager->createInvoice($invoiceFind);
        $this->invoicesRepository->getDocumentManager()->flush();

        self::assertEquals(Invoice::INVOICE, $invoiceFind->getStatus());
    }

    /**
     * @throws MongoDBException
     */
    public function testPayInvoice(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);
        $this->invoiceManager->payInvoice($invoiceFind);
        $this->invoicesRepository->getDocumentManager()->flush();

        self::assertEquals(Invoice::PAY, $invoiceFind->getStatus());
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function testCancelInvoice(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);
        $this->invoiceManager->createInvoice($invoiceFind);
        $this->invoiceManager->cancelInvoice($invoiceFind);
        $this->invoicesRepository->getDocumentManager()->flush();

        self::assertEquals(Invoice::CANCEL, $invoiceFind->getStatus());
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function testDeleteShoppingCart(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);
        $this->invoiceManager->deleteShoppingCart($invoiceFind);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);

        self::assertNull($invoiceFind);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function testDeleteProductsToShoppingCart(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $this->invoiceManager->deleteProductToShoppingCart($this->user, '65047fae8ff348-Jabon');
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);

        self::assertNull($invoiceFind);
    }

    /**
     * @throws MongoDBException
     */
    public function testDeleteInvoice(): void
    {
        $this->invoiceManager->addProductsToShoppingCart($this->products, $this->user);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByUserAndStatus($this->user, Invoice::SHOPPINGCART);
        $this->invoiceManager->createInvoice($invoiceFind);
        $this->invoiceManager->deleteInvoice($invoiceFind);
        $this->invoicesRepository->getDocumentManager()->flush();
        $invoiceFind = $this->invoicesRepository->findByCode($invoiceFind->getCode());

        self::assertNull($invoiceFind);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->invoicesRepository = $container->get(InvoicesRepository::class);
        $this->invoiceManager = $container->get(InvoiceManager::class);
        $this->user = (new User())
            ->setName('userTest')
            ->setDocument('100');
        $product = (new Product())
            ->setName('Jabon')
            ->setCode('65047fae8ff348-Jabon')
            ->setAmount(100)
        ;
        $this->products = new ArrayCollection();
        $this->products->add((new ProductInvoice())
            ->setName('Jabon')
            ->setCode('65047fae8ff348-Jabon')
            ->setAmount(10)
        );
        $this->invoicesRepository->getDocumentManager()->persist($this->user);
        $this->invoicesRepository->getDocumentManager()->persist($product);
        $this->invoicesRepository->getDocumentManager()->flush();
    }

    protected function tearDown(): void
    {
        $this->invoicesRepository->getDocumentManager()->getSchemaManager()->dropDatabases();

        unset(
            $this->invoiceManager,
            $this->invoicesRepository,
            $this->user,
            $this->products
        );
    }
}