<?php

declare(strict_types=1);

namespace App\Managers;

use App\Document\Product;
use App\Repository\ProductRepository;
use Symfony\Contracts\Service\Attribute\Required;

class ProductManager
{
    private ProductRepository $productRepository;

    #[Required]
    public function setProductRepository(ProductRepository $productRepository): void
    {
        $this->productRepository = $productRepository;
    }

    public function addProduct(Product $product): void
    {
        $product->setCode(str_ireplace(" ", "-", uniqid(). "-" . $product->getName()));
        $this->productRepository->getDocumentManager()->persist($product);
    }

    public function updateProduct(Product $product): void
    {
        $productUpdate = $this->productRepository->findByCode($product->getCode());
        $productUpdate->setAmount($product->getAmount());
        $this->productRepository->getDocumentManager()->persist($productUpdate);
    }

    public function deleteProduct(Product $product): void
    {
        $this->productRepository->getDocumentManager()->remove($product);
    }
}
