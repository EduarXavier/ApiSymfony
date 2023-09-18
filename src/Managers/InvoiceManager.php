<?php

declare(strict_types=1);

namespace App\Managers;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\ProductInvoice;
use App\Document\User;
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


class InvoiceManager
{
    private InvoicesRepository $invoicesRepository;
    private ProductManager $productManager;
    private SerializerInterface $serializer;
    private ProductRepository $productRepository;

    #[Required]
    public function setInvoicesRepository(InvoicesRepository $invoicesRepository): void
    {
        $this->invoicesRepository = $invoicesRepository;
    }

    #[Required]
    public function setProductManager(ProductManager $productManager): void
    {
        $this->productManager = $productManager;
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
    public function addProductsToShoppingCart(Collection $products, User $user): bool
    {
        $shoppingCart = $this->invoicesRepository->findByUserAndStatus($user, Invoice::SHOPPINGCART);

        if ($shoppingCart) {
            return $this->addToExistingCart($products, $shoppingCart);
        }

        return $this->createNewCart($products, $user);
    }

    public function invoiceResume(User $user, ?string $order ): ArrayCollection
    {
        $invoices = $this->invoicesRepository->findNotCancelByUser($user);
        $products = new ArrayCollection();

        foreach ($invoices as $invoice) {
            foreach ($invoice->getProducts() as $product) {
                $found = false;

                foreach ($products as $key => $productArray) {
                    if ($product->getCode() == $productArray->getCode()) {
                        $productArray->setAmount($product->getAmount() + $productArray->getAmount());
                        $products->set($key, $productArray);
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $products->add($product);
                }
            }
        }

        $products = $products->toArray();

        switch ($order) {
            case 'price' : usort($products, fn($a, $b) => $b->getPrice() - $a->getPrice());
                break;
            case 'amount' : usort($products, fn($a, $b) => $b->getAmount() - $a->getAmount());
                break;
            case 'total' : usort($products, fn($a, $b) => $b->getAmount() - $a->getAmount());
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
                    $shoppingCart->removeProduct($productUser);
                    $productJson = $this->serializer->serialize($productShop, 'json');
                    $productInvoice = $this->serializer->deserialize($productJson, ProductInvoice::class, 'json');
                    $productInvoice->setAmount($productUser->getAmount() + $product->getAmount());
                    $existingProduct = $productUser;
                    $shoppingCart->addProduct($productInvoice);
                    break;
                }
            }

            if ($existingProduct == null) {
                $amount = $product->getAmount();
                $productJson = $this->serializer->serialize($productShop, 'json');
                $product = $this->serializer->deserialize($productJson, ProductInvoice::class, 'json');
                $product->setAmount($amount);
                $shoppingCart->addProduct($product);
            }

            $this->updateProductAndCheckAvailability($productShop, $product->getAmount());
        }

        return true;
    }

    /**
     * @throws MappingException
     * @throws LockException
     * @throws Exception
     */
    private function createNewCart(Collection $products, User $user): bool
    {
        $invoices = new Invoice();
        $invoices->setUser($user);
        $invoices->setDate($this->getDate());
        $invoices->setCode(str_ireplace(' ', '-', uniqid(). '-' . $user->getDocument()));
        $invoices->setStatus(Invoice::SHOPPINGCART);;

        foreach ($products as $product) {

            $productShop = $this->productRepository->findByCode($product->getCode());

            if (!$productShop) {
                return false;
            }

            $productJson = $this->serializer->serialize($productShop, 'json');
            $productInvoice = $this->serializer->deserialize($productJson, ProductInvoice::class, 'json');
            $productInvoice->setAmount($product->getAmount());
            $invoices->addProduct($productInvoice);
            $this->invoicesRepository->getDocumentManager()->persist($invoices);
            $this->updateProductAndCheckAvailability($productShop, $product->getAmount());
        }

        $this->invoicesRepository->getDocumentManager()->persist($invoices);

        return true;
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function createInvoice(Invoice $invoice): void
    {
        $invoice->setDate($this->getDate());
        $invoice->setStatus(Invoice::INVOICE);
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function payInvoice(Invoice $invoice): void
    {
        $invoice->setDate($this->getDate());
        $invoice->setStatus(Invoice::PAY);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     * @throws Exception
     */
    public function cancelInvoice(Invoice $invoice): bool
    {
        if ($invoice->getStatus() == Invoice::INVOICE) {
            $invoice->setDate($this->getDate());
            $invoice->setStatus(Invoice::CANCEL);

            foreach ($invoice->getProducts() as $product) {
                $productFind = $this->productRepository->findByCode($product->getCode());
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productManager->updateProduct($productFind);
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
        foreach ($shoppingCart->getProducts() as $product) {
           $this->deleteProductToShoppingCart($shoppingCart->getUSer(), $product->getCode());
        }

        return true;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteProductToShoppingCart(User $user, string $code): bool
    {
        $shoppingCart = $this->invoicesRepository->findByUserAndStatus($user, Invoice::SHOPPINGCART);

        if ($shoppingCart->getProducts()->count() == 1) {
            foreach ($shoppingCart->getProducts() as $product) {
                $productFind = $this->productRepository->findByCode($code);
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productManager->updateProduct($productFind);
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
                $this->productManager->updateProduct($productFind);
                $this->productManager->updateProduct($productFind);

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
    private function getDate(): DateTime
    {
        return new DateTime('now', new DateTimeZone('America/Bogota'));
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
            $this->productManager->updateProduct($productShop);
        } else {
            throw new Exception('No hay tantos productos', Response::HTTP_BAD_REQUEST);
        }
    }
}
