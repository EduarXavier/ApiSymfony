<?php

declare(strict_types=1);

namespace App\Services;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\ProductInvoice;
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
    private ProductService $productService;
    private ProductRepository $productRepository;

    public function __construct (
        InvoicesRepository $invoicesRepository,
        ProductRepository $productRepository,
        ProductService $productService
    ) {
        $this->invoicesRepository = $invoicesRepository;
        $this->productService = $productService;
        $this->productRepository = $productRepository;
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function addProductsToShoppingCart(Collection $products, UserInvoice $user): ?DocumentManager
    {
        $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus($user->getDocument(), "shopping-cart");

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
        } else if ($order == "amount") {
            usort($products, function ($a, $b) {
                return $b->getAmount() - $a->getAmount();
            });
        } else if ($order == "total") {
            usort($products, function ($a, $b) {
                return $b->getAmount() * $b->getPrice() - $a->getAmount() * $a->getPrice();
            });
        } else {
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
    public function addToExistingCart(Collection $products, Invoice $shoppingCart): DocumentManager|bool
    {
        $productsUser = clone $shoppingCart->getProducts();

        foreach ($products as $product) {
            $productShop = clone $this->productRepository->findByCode($product->getCode());

            if (!$productShop) {
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
                $product = new ProductInvoice();
                $product->setProduct($productShop);
                $product->setAmount($amount);
                $productsUser->add($product);
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

            $productInvoice = new ProductInvoice();
            $productInvoice->setProduct($productShop);
            $productInvoice->setAmount($product->getAmount());
            $invoices->addProducts($productInvoice);
            $this->invoicesRepository->getDocumentManager()->persist($invoices);
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
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $invoice->setDate($fecha->format("Y-m-d H:i:s"));
        $invoice->setStatus("invoice");

        return $this->invoicesRepository->getDocumentManager();
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
            $this->productService->updateProduct($productShop);
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
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $invoice->setDate($fecha->format("Y-m-d H:i:s"));
        $invoice->setStatus("pay");

        return $this->invoicesRepository->getDocumentManager();
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

            foreach ($invoice->getProducts() as $product) {
                $productFind = $this->productRepository->findById($product->getId());
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productService->updateProduct($productFind);
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
            foreach ($shoppingCart->getProducts() as $product) {
                $productShop = $this->productRepository->findById($product->getId());
                $newAmount = $productShop->getAmount() + $product->getAmount();
                $productShop->setAmount($newAmount);
                $this->productService->updateProduct($productShop);
            }

            $invoice = $this->invoicesRepository->findByCode($shoppingCart->getCode());
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
        $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus($user->getDocument(), "shopping-cart");

        if (count($shoppingCart->getProducts()) == 1) {
            foreach ($shoppingCart->getProducts() as $product) {
                $productFind = $this->productRepository->findById($idProduct);
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productService->updateProduct($productFind);

                return $this->deleteInvoice($shoppingCart);
            }
        }


        foreach ($shoppingCart->getProducts() as $product) {
            if ($product->getId() == $idProduct) {
                $shoppingCart->removeProduct($product);
                $this->invoicesRepository->getDocumentManager()->persist($shoppingCart);

                $productFind = $this->productRepository->findById($idProduct);
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productService->updateProduct($productFind);

                return $this->invoicesRepository->getDocumentManager();
            }
        }

        return false;
    }

    public function deleteInvoice(Invoice $invoice): DocumentManager
    {
        $this->invoicesRepository->getDocumentManager()->persist($invoice);
        $this->invoicesRepository->getDocumentManager()->remove($invoice);

        return $this->invoicesRepository->getDocumentManager();
    }

}