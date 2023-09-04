<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\User;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class InvoicesRepository extends ServiceDocumentRepository
{
    private ProductRepository $productRepository;
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry, $documentClass, ProductRepository $productRepository)
    {
        parent::__construct($registry, $documentClass);
        $this->productRepository = $productRepository;
        $this->documentManager = $this->getDocumentManager();
    }

    public function findAllByUser(User $user): array
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findBy(["user.document" => $user->getDocument()], ['date' => 'DESC'], limit: 20);
    }

    public function findAllForStatus(User $user, string $status): array
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findBy(["user.document" => $user->getDocument(), "status" => $status], ['date' => 'DESC'], limit: 20);
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function addProductsToShoppingCart(Collection $products, User $user): ?bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($user->getDocument(), "shopping-cart");

        if ($shoppingCart) {
            $this->addToExistingCart($products, $shoppingCart);
        } else {
           $this->createNewCart($products, $user);
        }

        $this->documentManager->flush();
        return true;
    }

    /**
     * @throws MappingException
     * @throws LockException
     * @throws Exception
     */
    private function addToExistingCart(Collection $products, Invoice $shoppingCart): void
    {

        date_default_timezone_set('America/Bogota');
        $productsUser = $shoppingCart->getProducts();

        foreach ($products as $product) {
            $productShop = $this->productRepository->findById($product->getId());

            if ($productShop) {

                $existingProduct = null;

                foreach ($productsUser as $productArray) {
                    if ($productArray->getId() === $product->getId()) {
                        $shoppingCart->removeProduct($productArray);
                        $existingProduct = $productArray;
                        $productArray->setAmount($productArray->getAmount() + $product->getAmount());
                        $shoppingCart->addProducts($productArray);

                        break;
                    }
                }

                if ($existingProduct == null) {
                    $shoppingCart->addProducts($product);
                }

                $this->updateProductAndCheckAvailability($productShop, $product->getAmount());
            }
        }

        $shoppingCart->setProducts($productsUser);
    }

    /**
     * @throws MappingException
     * @throws LockException
     * @throws Exception
     */
    private function createNewCart(Collection $products, User $user): void
    {
        date_default_timezone_set('America/Bogota');
        $invoices = new Invoice();
        $invoices->setCode(password_hash(date("Y-m-d H:i:s"), PASSWORD_BCRYPT));

        foreach ($products as $product) {
            $productShop = $this->productRepository->findByCode($product->getCode());

            if ($productShop) {
                $this->updateProductAndCheckAvailability($productShop, $product->getAmount());

                $productShop->setAmount($product->getAmount());
                $invoices->addProducts($productShop);
            }
        }

        $this->documentManager->persist($user);
        $invoices->setUser($user);
        $invoices->setDate(date("Y-m-d H:i:s"));
        $invoices->setStatus("shopping-cart");;
        $this->documentManager->persist($invoices);
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    private function updateProductAndCheckAvailability(Product $productShop, int $amount): void
    {
        date_default_timezone_set('America/Bogota');
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
     * @throws MappingException
     */
    public function updateShoppingCart(Collection $products, User $user): ?bool
    {
        date_default_timezone_set('America/Bogota');
        $shoppingCart = $this->findByDocumentAndStatus($user->getDocument(), "shopping-cart");

        if($shoppingCart) {

            foreach ($shoppingCart->getProducts() as $product) {
                $productShop = $this->productRepository->findById($product->getId());
                $newAmount = $productShop->getAmount() + $product->getAmount();
                $productShop->setAmount($newAmount);
                $this->productRepository->updateProduct($productShop);
            }

            $shoppingCart->getProducts()->clear();
            $this->documentManager->flush();

            return $this->addProductsToShoppingCart($products, $user);
        }

        return false;
    }

    /**
     * @throws MongoDBException
     */
    public function createInvoice(Invoice $invoice): bool
    {
        date_default_timezone_set('America/Bogota');
        $invoice->setDate(date("Y-m-d H:i:s"));
        $invoice->setStatus("invoice");
        $this->documentManager->flush();

        return true;
    }

    public function findByDocumentAndStatus(string $document, string $status)
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findOneBy(["user.document" => $document, "status" => $status]);
    }

    public function findById(string $id, string $status)
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $status ? $repository->findOneBy(["id" => $id]) : $repository->findOneBy(["id" => $id, "status" => $status]);
    }

    public function findByCode(string $code)
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findOneBy(["code" => $code]);
    }

    /**
     * @throws MongoDBException
     */
    public function payInvoice(Invoice $invoice): bool
    {
        date_default_timezone_set('America/Bogota');
        $invoice->setDate(date("Y-m-d H:i:s"));
        $invoice->setStatus("pay");
        $this->documentManager->flush();

        return true;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteInvoice(Invoice $invoice): bool
    {
        date_default_timezone_set('America/Bogota');
        if ($invoice->getStatus() == "invoice") {
            $products = $invoice->getProducts();
            $invoice->setStatus("shopping-cart");
            $this->documentManager->flush();
            $this->updateShoppingCart(new ArrayCollection(), $invoice->getUser());
            $invoice->setProducts($products);
            $invoice->setDate(date("Y-m-d H:i:s"));
            $invoice->setStatus("cancel");
            $this->documentManager->flush();

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
        date_default_timezone_set('America/Bogota');
        if ($shoppingCart->getStatus() == "shopping-cart") {
            $shoppingCart->setDate(date("Y-m-d H:i:s"));
            $this->documentManager->flush();
            $this->updateShoppingCart(new ArrayCollection(), $shoppingCart->getUser());

            return true;
        }

        return false;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteProductToShoppingCart(string $document, string $idProduct): bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($document, "shopping-cart");

        foreach ($shoppingCart?->getProducts() as $product) {
            if ($product->getId() == $idProduct) {
                $shoppingCart->removeProduct($product);
            }
        }

        $this->getDocumentManager()->flush();

        return true;
    }
}
