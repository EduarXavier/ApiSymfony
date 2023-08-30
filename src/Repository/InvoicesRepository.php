<?php

namespace App\Repository;

use App\Document\Invoice;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;

class InvoicesRepository implements InvoicesRepositoryInterface
{

    private ProductRepositoryInterface $productRepository;

    public function __construct()
    {
        date_default_timezone_set('America/Bogota');
        $this->productRepository = new ProductRepository();
    }

    public function findAll(DocumentManager $documentManager)
    {
        $repository = $documentManager->getRepository(Invoice::class);
        $invoices = $repository->findAll();

        return $invoices;
    }

    public function AddProductsToshoppingCart
    (
        array $products,
        string $document,
        DocumentManager $documentManager
    ): ?bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($document, "shopping-cart", $documentManager);

        if($shoppingCart)
        {
            $productsUser = $shoppingCart->getProducts();

            foreach ($products as $product)
            {
                $productShop = $this->productRepository->findById($product["id"], $documentManager);
                if($productShop)
                {
                    $existingProduct = $this->findProductByIdInArray($product["id"], $productsUser);

                    if ($existingProduct)
                    {
                        $existingProduct["amount"] += intval($product["amount"]);
                        $productsUser[array_search($existingProduct["id"], $productsUser)] = $existingProduct;

                    }
                    else
                    {
                        $productsUser[] = $product;
                    }

                    $newAmuntProduct = $productShop->getAmount() - $product['amount'];

                    if($newAmuntProduct >= 0)
                    {
                        $productShop->setAmount($newAmuntProduct);

                        $this->productRepository->updateProduct($productShop, $documentManager);
                    }
                    else
                    {
                        throw new \Exception("No hay tantos productos", 400);
                    }
                }
            }

            $shoppingCart->setProducts($productsUser);
            $documentManager->flush();

            return true;
        }
        else
        {
            $invoices = new Invoice();

            $invoices->setUserDocument($document);
            $invoices->setDate(date("Y-m-d H:i:s"));
            $invoices->setStatus("shopping-cart");

            $productsAdd = array();

            foreach ($products as $product)
            {
                $productShop = $this->productRepository->findById($product["id"], $documentManager);

                if($productShop)
                {
                    $newAmuntProduct = $productShop->getAmount() - $product['amount'];

                    if($newAmuntProduct >= 0)
                    {
                        $productShop->setAmount($newAmuntProduct);

                        $this->productRepository->updateProduct($productShop, $documentManager);
                    }
                    else
                    {
                        throw new \Exception("No hay tantos productos", 400);
                    }

                    $productsAdd[] = $product;
                }
            }

            $invoices->setProducts($productsAdd);

            $documentManager->persist($invoices);
            $documentManager->flush();

            return true;
        }

    }

    public function updateShoppingCart
    (
        array $products,
        string $document,
        DocumentManager $documentManager
    ): ?bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($document, "shopping-cart", $documentManager);

        if($shoppingCart)
        {

            foreach ($shoppingCart->getProducts() as $product)
            {
                $productShop = $this->productRepository->findById($product["id"], $documentManager);
                $newAmount = $productShop->getAmount() + $product['amount'];
                $productShop->setAmount($newAmount);

                $this->productRepository->updateProduct($productShop, $documentManager);

            }

            $shoppingCart->setProducts(array());
            $documentManager->flush();

            return $this->AddProductsToshoppingCart($products, $document, $documentManager);

        }

        return false;
    }

    /**
     * @throws MongoDBException
     */
    public function createInvoice
    (
        Invoice $invoices,
        DocumentManager $documentManager
    ): bool
    {
        $invoices->setDate(date("Y-m-d H:i:s"));
        $invoices->setStatus("invoice");
        $documentManager->flush();

        return true;
    }

    public function findByDocumentAndStatus
    (
        string $document,
        string $status,
        DocumentManager $documentManager
    )
    {
        $repository = $documentManager->getRepository(Invoice::class);
        $invoice = $repository->findOneBy(["userDocument" => $document, "status" => "shopping-cart"]);

        return $invoice;

    }

    public function findById
    (
        string $document,
        DocumentManager $documentManager
    )
    {
        $repository = $documentManager->getRepository(Invoice::class);
        $invoice = $repository->findOneBy(["id" => $document]);

        return $invoice;
    }

    /**
     * @throws MongoDBException
     */
    public function payInvoice
    (
        Invoice $invoice,
        DocumentManager $documentManager
    )
    {
        $invoice->setStatus("pay");
        $documentManager->flush();

        return true;
    }

    public function deleteInvoice(Invoice $invoice, DocumentManager $documentManager)
    {
        if($invoice->getStatus() == "invoice")
        {
            $products = $invoice->getProducts();
            $invoice->setStatus("shopping-cart");
            $documentManager->flush();

            $this->updateShoppingCart(array(), $invoice->getUserDocument(), $documentManager);

            $invoice->setProducts($products);
            $invoice->setStatus("cancel");
            $documentManager->flush();

            return true;
        }

        return false;
    }

    private function findProductByIdInArray
    (
        string $productId,
        array $productArray
    ): ?array
    {
        foreach ($productArray as $product)
        {
            if ($product["id"] === $productId)
            {
                return $product;
            }
        }
        return null;
    }

}
