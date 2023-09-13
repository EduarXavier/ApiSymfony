<?php

declare(strict_types=1);

namespace App\Services;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\ProductInvoice;
use App\Document\UserInvoice;
use App\Repository\InvoicesRepository;
use App\Repository\ProductRepository;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\Attribute\Required;


class InvoiceService
{
    private InvoicesRepository $invoicesRepository;
    private ProductService $productService;
    private SerializerInterface $serializer;
    private ProductRepository $productRepository;

    #[Required]
    public function setInvoicesRepository(InvoicesRepository $invoicesRepository): void
    {
        $this->invoicesRepository = $invoicesRepository;
    }

    #[Required]
    public function setProductService(ProductService $productService): void
    {
        $this->productService = $productService;
    }

    #[Required]
    public function setSerializerInterface(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    #[Required]
    public function setProductRepository(ProductRepository $productRepository): void
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function addProductsToShoppingCart(Collection $products, UserInvoice $user): bool
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

        switch ($order) {
            case "price" : usort($products, fn($a, $b) => $b->getPrice() - $a->getPrice());
                break;
            case "amount" : usort($products, fn($a, $b) => $b->getAmount() - $a->getAmount());
                break;
            case "total" : usort($products, fn($a, $b) => $b->getAmount() - $a->getAmount());
                break;
            default : usort($products, fn($a, $b) => strcmp($a->getName(), $b->getName()));
        }

        return new ArrayCollection($products);

    }

    /**
     * @throws MappingException
     * @throws LockException
     * @throws Exception
     */
    public function addToExistingCart(Collection $products, Invoice $shoppingCart): bool
    {
        $productsUser = $shoppingCart->getProducts();

        foreach ($products as $product) {
            $productShop = $this->productRepository->findByCode($product->getCode());

            if (!$productShop) {
                break;
            }

            $existingProduct = null;

            foreach ($productsUser as $key => $productUser) {
                if ($productUser->getCode() === $product->getCode()) {
                    $productsUser->remove($key);
                    $existingProduct = $productUser;
                    $productUser->setAmount($productUser->getAmount() + $product->getAmount());
                    $productsUser->add($productUser);
                    break;
                }
            }

            if ($existingProduct == null) {
                $amount = $product->getAmount();
                $productJson = $this->serializer->serialize($productShop, "json");
                $product = $this->serializer->deserialize($productJson, ProductInvoice::class, "json");
                $product->setAmount($amount);
                $productsUser->add($product);
            }

            $shoppingCart->setProducts($productsUser);

            $this->updateProductAndCheckAvailability($productShop, $product->getAmount());

        }

        return true;
    }

    /**
     * @throws MappingException
     * @throws LockException
     * @throws Exception
     */
    private function createNewCart(Collection $products, UserInvoice $user): bool
    {
        $invoices = new Invoice();
        $this->invoicesRepository->getDocumentManager()->persist($user);
        $invoices->setUser($user);
        $invoices->setDate($this->getDate());
        $invoices->setCode(str_ireplace(" ", "-", uniqid(). "-" . $user->getDocument()));
        $invoices->setStatus("shopping-cart");;

        foreach ($products as $product) {

            $productShop = $this->productRepository->findByCode($product->getCode());

            if (!$productShop) {
                return false;
            }

            $productJson = $this->serializer->serialize($productShop, "json");
            $productInvoice = $this->serializer->deserialize($productJson, ProductInvoice::class, "json");
            $productInvoice->setAmount($product->getAmount());
            $invoices->addProducts($productInvoice);
            $this->invoicesRepository->getDocumentManager()->persist($invoices);
            $this->updateProductAndCheckAvailability($productShop, $product->getAmount());
        }

        return true;
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function createInvoice(Invoice $invoice): void
    {
        $invoice->setDate($this->getDate());
        $invoice->setStatus("invoice");
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
    public function payInvoice(Invoice $invoice): void
    {
        $invoice->setDate($this->getDate());
        $invoice->setStatus("pay");
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     * @throws Exception
     */
    public function cancelInvoice(Invoice $invoice): bool
    {
        if ($invoice->getStatus() == "invoice") {
            $invoice->setDate($this->getDate());
            $invoice->setStatus("cancel");

            foreach ($invoice->getProducts() as $product) {
                $productFind = $this->productRepository->findById($product->getId());
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productService->updateProduct($productFind);
            }

            return true;
        }

        return false;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteShoppingCart(Invoice $shoppingCart): bool
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

            return true;
        }

        return false;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteProductToShoppingCart(UserInvoice $user, string $code): bool
    {
        $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus($user->getDocument(), "shopping-cart");

        if (count($shoppingCart->getProducts()) == 1) {
            foreach ($shoppingCart->getProducts() as $product) {
                $productFind = $this->productRepository->findByCode($code);
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productService->updateProduct($productFind);
                $this->deleteInvoice($shoppingCart);

                return true;
            }
        }

        foreach ($shoppingCart->getProducts() as $product) {
            if ($product->getCode() == $code) {
                $shoppingCart->removeProduct($product);
                $this->invoicesRepository->getDocumentManager()->persist($shoppingCart);

                $productFind = $this->productRepository->findByCode($code);
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productService->updateProduct($productFind);

                return true;
            }
        }

        return false;
    }

    public function deleteInvoice(Invoice $invoice): void
    {
        $this->invoicesRepository->getDocumentManager()->persist($invoice);
        $this->invoicesRepository->getDocumentManager()->remove($invoice);
    }

    /**
     * @throws Exception
     */
    protected function getDate(): string
    {
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));

        return $fecha->format("Y-m-d H:i:s");
    }
}
