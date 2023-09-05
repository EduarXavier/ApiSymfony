<?php

namespace App\Services;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\User;
use App\Repository\InvoicesRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;

class InvoiceService
{
    private InvoicesRepository $invoicesRepository;
    private UserRepository $userRepository;
    private ProductRepository $productRepository;

    public function __construct(
        UserRepository $userRepository,
        InvoicesRepository $invoicesRepository,
        ProductRepository $productRepository
    ) {
        $this->userRepository = $userRepository;
        $this->invoicesRepository = $invoicesRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    public function deleteProductToShoppingCart(User $user, string $idProduct): bool
    {
        $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus($user->getDocument(), "shopping-cart");
        $products = new ArrayCollection();

        foreach ($shoppingCart?->getProducts() as $product) {
            if ($product->getId() != $idProduct) {
                $productArray = clone $product;
                $products[] = $productArray;
            }
        }

        if(count($products) == 0)
        {
            $this->invoicesRepository->deleteShoppingCart($shoppingCart);
        }

        $this->invoicesRepository->updateShoppingCart($products, $user);

        return true;
    }
}