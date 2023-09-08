<?php

declare(strict_types=1);

namespace App\Services;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\User;
use App\Document\UserInvoice;
use App\Repository\InvoicesRepository;
use App\Repository\ProductRepository;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Symfony\Component\HttpFoundation\Response;


class InvoiceService
{
    private InvoicesRepository $invoicesRepository;
    private ProductRepository $productRepository;

    public function __construct (
        InvoicesRepository $invoicesRepository,
        ProductRepository $productRepository
    ) {
        $this->invoicesRepository = $invoicesRepository;
        $this->productRepository = $productRepository;
    }

    public function findAllByUser(UserInvoice $user): array
    {
        return $this->invoicesRepository->findAllByUser($user);
    }

    public function findAllForStatus(UserInvoice $user, string $status): array
    {
        return $this->invoicesRepository->findAllForStatus($user, $status);
    }

    public function findById(string $id, string $status)
    {
        return $this->invoicesRepository->findById($id, $status);
    }

    public function findByCode(string $code)
    {
        return $this->invoicesRepository->findByCode($code);
    }

    public function findByDocumentAndStatus(string $document, string $status): ?Invoice
    {
        return $this->invoicesRepository->findByDocumentAndStatus($document, $status);
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function addProductsToShoppingCart(Collection $products, UserInvoice $user): ?DocumentManager
    {
        $shoppingCart = $this->findByDocumentAndStatus($user->getDocument(), "shopping-cart");

        if ($shoppingCart) {
            return $this->addToExistingCart($products, $shoppingCart);
        }

        return $this->createNewCart($products, $user);
    }

    public function invoiceResume(UserInvoice $user, ?string $order ): ArrayCollection
    {
        $invoices = $this->invoicesRepository->findAllByUser($user);
        $products = [];

        foreach ($invoices as $invoice) {
            foreach ($invoice->getProducts() as $product) {
                if (isset($products[$product->getId()])) {
                    $products[$product->getId()]->setAmount($products[$product->getId()]->getAmount() + $product->getAmount());
                } else {
                    $products[$product->getId()] = $product;
                }
            }
        }

        if ($order == "price") {
            usort($products, function ($a, $b) {
                return $b->getPrice() - $a->getPrice();
            });
        }
        else if ($order == "amount") {
            usort($products, function ($a, $b) {
                return $b->getAmount() - $a->getAmount();
            });
        }
        else if ($order == "total") {
            usort($products, function ($a, $b) {
                return $b->getAmount() * $b->getPrice() - $a->getAmount() * $a->getPrice();
            });
        }
        else {
            usort($products, function ($a, $b) {
                return strcmp($a->getName(), $b->getName());
            });
        }

        return new ArrayCollection($products);

    }

    /**
     * @throws MappingException
     * @throws LockException
     * @throws Exception
     */
    private function addToExistingCart(Collection $products, Invoice $shoppingCart): DocumentManager
    {
        $productsUser = clone $shoppingCart->getProducts();

        foreach ($products as $product){
            $productShop = clone $this->productRepository->findByCode($product->getCode());

            if(!$productShop){
                break;
            }

            $existingProduct = null;

            foreach ($productsUser as $key => $productUser) {
                if ($productUser->getCode() === $product->getCode()) {
                    $productsUser->remove($key);
                    $existingProduct = $productUser;
                    $productUser->setAmount($productUser->getAmount() + $product->getAmount());
                    $productsUser->add(clone $productUser);

                    break;
                }
            }

            if ($existingProduct == null) {
                $amount = $product->getAmount();
                $product = clone $productShop;
                $product->setAmount($amount);
                $productsUser->add(clone $product);
            }

            $shoppingCart->setProducts($productsUser);

            $this->updateProductAndCheckAvailability($productShop, $product->getAmount());

        }

        return $this->invoicesRepository->getDocumentManager();
    }

    /**
     * @throws MappingException
     * @throws LockException
     * @throws Exception
     */
    private function createNewCart(Collection $products, UserInvoice $user): ?DocumentManager
    {
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $invoices = new Invoice();
        $this->invoicesRepository->getDocumentManager()->persist($user);
        $invoices->setUser($user);
        $invoices->setDate($fecha->format("Y-m-d H:i:s"));
        $invoices->setCode(str_ireplace(" ", "-", uniqid(). "-" . $user->getDocument()));
        $invoices->setStatus("shopping-cart");;

        foreach ($products as $product) {

            $productShop = $this->productRepository->findByCode($product->getCode());

            if (!$productShop) {
                return null;
            }

            $productShop = clone $productShop;
            $amount = $productShop->getAmount();
            $productShop->setAmount($product->getAmount());
            $invoices->addProducts(clone $productShop);
            $this->invoicesRepository->getDocumentManager()->persist($invoices);
            $productShop->setAmount($amount);
            $this->updateProductAndCheckAvailability($productShop, $product->getAmount());

        }

        return $this->invoicesRepository->getDocumentManager();
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function createInvoice(Invoice $invoice): DocumentManager
    {
        return $this->invoicesRepository->createInvoice($invoice);
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    private function updateProductAndCheckAvailability(Product $productShop, int $amount): void
    {
        $newAmountProduct = $productShop->getAmount() - $amount;

        if ($newAmountProduct >= 0) {
            $productShop->setAmount($newAmountProduct);
            $this->productRepository->updateProduct($productShop);
        } else {
            throw new Exception("No hay tantos productos", Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function payInvoice(Invoice $invoice): DocumentManager
    {
        return $this->invoicesRepository->payInvoice($invoice);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     * @throws Exception
     */
    public function cancelInvoice(Invoice $invoice): DocumentManager|bool
    {
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));

        if ($invoice->getStatus() == "invoice") {
            $invoice->setDate($fecha->format("Y-m-d H:i:s"));
            $invoice->setStatus("cancel");

            foreach ($invoice->getProducts() as $product){
                $productFind = $this->productRepository->findById($product->getId());
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productRepository->updateProduct($productFind);
            }

            return $this->invoicesRepository->getDocumentManager();
        }

        return false;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteShoppingCart(Invoice $shoppingCart): DocumentManager|bool
    {
        if ($shoppingCart->getStatus() == "shopping-cart") {
            foreach ($shoppingCart->getProducts() as $product){
                $productShop = $this->productRepository->findById($product->getId());
                $newAmount = $productShop->getAmount() + $product->getAmount();
                $productShop->setAmount($newAmount);
                $this->productRepository->updateProduct($productShop);
            }

            $invoice = $this->findByCode($shoppingCart->getCode());
            $invoice->setProducts(new ArrayCollection());

            return $this->invoicesRepository->getDocumentManager();
        }

        return false;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteProductToShoppingCart(UserInvoice $user, string $idProduct): DocumentManager|bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($user->getDocument(), "shopping-cart");

        if (count($shoppingCart->getProducts()) == 1) {
            foreach ($shoppingCart->getProducts() as $product) {
                $productFind = $this->productRepository->findById($idProduct);
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productRepository->updateProduct($productFind);

                return $this->invoicesRepository->deleteInvoice($shoppingCart);
            }
        }


        foreach ($shoppingCart->getProducts() as $product) {
            if ($product->getId() == $idProduct){
                $shoppingCart->removeProduct($product);
                $this->invoicesRepository->getDocumentManager()->persist($shoppingCart);

                $productFind = $this->productRepository->findById($idProduct);
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productRepository->updateProduct($productFind);

                return $this->invoicesRepository->getDocumentManager();
            }
        }

        return false;
    }

}