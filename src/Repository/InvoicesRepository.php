<?php

namespace App\Repository;

use App\Document\Invoice;
use App\Document\Product;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;

class InvoicesRepository implements InvoicesRepositoryInterface
{
    private ProductRepositoryInterface $productRepository;

    public function __construct()
    {
        date_default_timezone_set('America/Bogota');
        $this->productRepository = new ProductRepository();
    }

    public function findAll(DocumentManager $documentManager): array
    {
        $repository = $documentManager->getRepository(Invoice::class);

        return $repository->findBy([], ['date' => 'DESC'], limit: 20);
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function addProductsToShoppingCart(array $products, string $document, DocumentManager $documentManager): ?bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($document, "shopping-cart", $documentManager);

        if ($shoppingCart) {
            $productsUser = $shoppingCart->getProducts();

            foreach ($products as $product) {
                $productShop = $this->productRepository->findById($product->getId(), $documentManager);

                if ($productShop) {

                    $existingProduct = null;
                    $contador = 0;

                    foreach ($productsUser as $productArray) {
                        if ($productArray["id"] === $product->getId()) {
                            $existingProduct = $productArray;
                            break;
                        }
                        $contador += 1;
                    }

                    if ($existingProduct) {
                        $existingProduct["amount"] += intval($product->getAmount());
                        $productsUser[$contador] = [
                            "id" => $product->getId(),
                            "amount" => $existingProduct["amount"]
                        ];
                    }
                    else {
                        $productsUser[$contador] = [
                            "id" => $product->getId(),
                            "amount" => $product->getAmount()
                        ];
                    }

                    $newAmountProduct = $productShop->getAmount() - $product->getAmount();

                    if ($newAmountProduct >= 0) {
                        $productShop->setAmount($newAmountProduct);
                        $this->productRepository->updateProduct($productShop, $documentManager);
                    }
                    else {
                        throw new Exception("No hay tantos productos", 400);
                    }
                }
            }

            $shoppingCart->setProducts($productsUser);
        } else {
            $invoices = new Invoice();
            $invoices->setUserDocument($document);
            $invoices->setDate(date("Y-m-d H:i:s"));
            $invoices->setStatus("shopping-cart");
            $productsAdd = array();

            foreach ($products as $product) {
                $productShop = $this->productRepository->findById($product->getId(), $documentManager);

                if ($productShop) {
                    $newAmountProduct = $productShop->getAmount() - $product->getAmount();

                    if ($newAmountProduct >= 0) {
                        $productShop->setAmount($newAmountProduct);
                        $this->productRepository->updateProduct($productShop, $documentManager);
                    } else {
                        throw new \Exception("No hay tantos productos", 400);
                    }

                    $productsAdd[] = [
                        "id" => $product->getId(),
                        "amount" => $product->getAmount()
                    ];
                }
            }

            $invoices->setProducts($productsAdd);
            $documentManager->persist($invoices);
        }

        $documentManager->flush();

        return true;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function updateShoppingCart(array $products, string $document, DocumentManager $documentManager): ?bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($document, "shopping-cart", $documentManager);

        if($shoppingCart) {

            foreach ($shoppingCart->getProducts() as $product) {
                $productShop = $this->productRepository->findById($product["id"], $documentManager);
                $newAmount = $productShop->getAmount() + $product["amount"];
                $productShop->setAmount($newAmount);
                $this->productRepository->updateProduct($productShop, $documentManager);
            }

            $shoppingCart->setProducts(array());
            $documentManager->flush();

            return $this->addProductsToShoppingCart($products, $document, $documentManager);
        }

        return false;
    }

    /**
     * @throws MongoDBException
     */
    public function createInvoice(Invoice $invoice, DocumentManager $documentManager): bool
    {
        $invoice->setDate(date("Y-m-d H:i:s"));
        $invoice->setStatus("invoice");
        $documentManager->flush();

        return true;
    }

    public function findByDocumentAndStatus(string $document, string $status, DocumentManager $documentManager)
    {
        $repository = $documentManager->getRepository(Invoice::class);

        return $repository->findOneBy(["userDocument" => $document, "status" => "shopping-cart"]);
    }

    public function findById(string $document, DocumentManager $documentManager)
    {
        $repository = $documentManager->getRepository(Invoice::class);

        return $repository->findOneBy(["id" => $document]);
    }

    /**
     * @throws MongoDBException
     */
    public function payInvoice(Invoice $invoice, DocumentManager $documentManager): bool
    {
        $invoice->setDate(date("Y-m-d H:i:s"));
        $invoice->setStatus("pay");
        $documentManager->flush();

        return true;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteInvoice(Invoice $invoice, DocumentManager $documentManager): bool
    {
        if ($invoice->getStatus() == "invoice") {
            $products = $invoice->getProducts();
            $invoice->setStatus("shopping-cart");
            $documentManager->flush();
            $this->updateShoppingCart(array(), $invoice->getUserDocument(), $documentManager);
            $invoice->setProducts($products);
            $invoice->setDate(date("Y-m-d H:i:s"));
            $invoice->setStatus("cancel");
            $documentManager->flush();

            return true;
        }

        return false;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteShoppingCart(Invoice $shoppingCart, DocumentManager $documentManager): bool
    {
        if ($shoppingCart->getStatus() == "shopping-cart") {
            $shoppingCart->setDate(date("Y-m-d H:i:s"));
            $documentManager->flush();
            $this->updateShoppingCart(array(), $shoppingCart->getUserDocument(), $documentManager);

            return true;
        }

        return false;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteProductToShoppingCart(string $document, string $idProduct, DocumentManager $documentManager): bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($document, "shopping-cart", $documentManager);
        $products = array();

        foreach ($shoppingCart?->getProducts() as $product) {
            if ($product["id"] != $idProduct) {
                $productArray = new Product();
                $productArray->setId($product["id"]);
                $productArray->setAmount($product["amount"]);
                $products[] = $productArray;
            }
        }

        $this->updateShoppingCart($products, $document, $documentManager);

        return true;
    }
}
