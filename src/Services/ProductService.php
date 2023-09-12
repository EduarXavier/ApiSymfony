<?php

namespace App\Services;

use App\Document\Product;
use App\Repository\ProductRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class ProductService
{
    private ProductRepository $productRepository;

    public function __construct (
        ProductRepository $productRepository,
    ) {
        $this->productRepository = $productRepository;
    }

    public function addProduct(Product $product): DocumentManager
    {
        $product->setCode(str_ireplace(" ", "-", uniqid(). "-" . $product->getName()));
        $this->productRepository->getDocumentManager()->persist($product);

        return $this->productRepository->getDocumentManager();
    }

    public function updateProduct(Product $product): DocumentManager
    {
        $productUpdate = $this->productRepository->findByCode($product->getCode());
        $productUpdate->setAmount($product->getAmount());
        $this->productRepository->getDocumentManager()->persist($productUpdate);

        return $this->productRepository->getDocumentManager();
    }

    public function deleteProduct(Product $product): DocumentManager
    {
        $this->productRepository->getDocumentManager()->remove($product);

        return $this->productRepository->getDocumentManager();
    }

}