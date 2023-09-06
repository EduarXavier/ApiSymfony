<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\User;
use DateTime;
use DateTimeZone;
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

    public function findByDocumentAndStatus(string $document, string $status): ?Invoice
    {
        $this->documentManager->clear();
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findOneBy(["user.document" => $document, "status" => $status]);
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
    }

    /**
     * @throws MappingException
     * @throws LockException
     * @throws Exception
     */
    private function createNewCart(Collection $products, User $user): void
    {
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $invoices = new Invoice();
        $this->documentManager->persist($user);
        $invoices->setUser($user);
        $invoices->setDate($fecha->format("Y-m-d H:i:s"));
        $invoices->setCode(str_ireplace(" ", "-", uniqid(). "-" . $user->getDocument()));
        $invoices->setStatus("shopping-cart");;

        foreach ($products as $product) {

            $productShop = clone $this->productRepository->findByCode($product->getCode());

            if ($productShop) {
                $amount = $productShop->getAmount();
                $productShop->setAmount($product->getAmount());
                $invoices->addProducts(clone $productShop);
                $this->documentManager->persist($invoices);
                $productShop->setAmount($amount);
                $this->updateProductAndCheckAvailability($productShop, $product->getAmount());
            }
        }
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function createInvoice(Invoice $invoice): bool
    {
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $invoice->setDate($fecha->format("Y-m-d H:i:s"));
        $invoice->setStatus("invoice");
        $this->documentManager->flush();

        return true;
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
    public function payInvoice(Invoice $invoice): bool
    {
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $invoice->setDate($fecha->format("Y-m-d H:i:s"));
        $invoice->setStatus("pay");
        $this->documentManager->flush();

        return true;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     * @throws Exception
     */
    public function cancelInvoice(Invoice $invoice): bool
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
        if ($shoppingCart->getStatus() == "shopping-cart") {
            foreach ($shoppingCart->getProducts() as $product){
                $productShop = $this->productRepository->findById($product->getId());
                $newAmount = $productShop->getAmount() + $product->getAmount();
                $productShop->setAmount($newAmount);
                $this->productRepository->updateProduct($productShop);
            }

            $invoice = $this->findByCode($shoppingCart->getCode());
            $invoice->setProducts(new ArrayCollection());
            $this->documentManager->flush();

            return true;
        }

        return false;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteProductToShoppingCart(User $user, string $idProduct): bool
    {
        $shoppingCart = $this->findByDocumentAndStatus($user->getDocument(), "shopping-cart");

        foreach ($shoppingCart->getProducts() as $product) {
            if ($product->getId() == $idProduct){
                $shoppingCart->removeProduct($product);
                $this->documentManager->flush();

                $productFind = $this->productRepository->findById($idProduct);
                $productFind->setAmount($product->getAmount() + $productFind->getAmount());
                $this->productRepository->updateProduct($productFind);

                return true;
            }
        }

        return true;
    }
}
