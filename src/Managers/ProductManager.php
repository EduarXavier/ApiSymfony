<?php

declare(strict_types=1);

namespace App\Managers;

use App\Document\Product;
use App\Repository\InvoicesRepository;
use App\Repository\ProductRepository;
use Symfony\Contracts\Service\Attribute\Required;

class ProductManager
{
    private ProductRepository $productRepository;
    private InvoicesRepository $invoicesRepository;

    #[Required]
    public function setProductRepository(ProductRepository $productRepository): void
    {
        $this->productRepository = $productRepository;
    }

    #[Required]
    public function setInvoiceRepository(InvoicesRepository $invoicesRepository): void
    {
        $this->invoicesRepository = $invoicesRepository;
    }

    public function addProduct(Product $product): void
    {
        $product->setCode(str_ireplace(" ", "-", uniqid(). "-" . $product->getName()));
        $product->setStatus("available");
        $this->productRepository->getDocumentManager()->persist($product);
    }

    public function updateProduct(Product $product): void
    {
        $productUpdate = $this->productRepository->findByCode($product->getCode());
        $productUpdate->setAmount($product->getAmount());
    }

    public function deleteProduct(Product $product): void
    {
        $invoices = $this->invoicesRepository->findByProduct($product);
        if ($invoices) {
            $product->setStatus('expired');
            $this->productRepository->getDocumentManager()->persist($product);
        } else {
            $this->productRepository->getDocumentManager()->remove($product);
        }
    }
}
