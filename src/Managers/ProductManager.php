<?php

declare(strict_types=1);

namespace App\Managers;

use App\Document\Product;
use App\Document\ProductInvoice;
use App\Repository\InvoicesRepository;
use App\Repository\ProductRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class ProductManager
{
    private ProductRepository $productRepository;
    private InvoicesRepository $invoicesRepository;
    private SerializerInterface $serializer;

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

    #[Required]
    public function setSerializerInterface(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function addProduct(Product $product): void
    {
        $product->setCode(str_ireplace(' ', '-', uniqid(). '-' . $product->getName()));
        $product->setStatus(Product::AVAILABLE);
        $this->productRepository->getDocumentManager()->persist($product);
    }

    public function updateProduct(Product $product): void
    {
        $productUpdate = $this->productRepository->findByCode($product->getCode());
        $productUpdate->setAmount($product->getAmount());
    }

    public function deleteProduct(Product $product): void
    {
        $productJson = $this->serializer->serialize($product, 'json');
        $productInvoice = $this->serializer->deserialize($productJson, ProductInvoice::class, 'json');
        $invoices = $this->invoicesRepository->findByProduct($productInvoice);
        if ($invoices) {
            $product->setStatus(Product::EXPIRED);
            $this->productRepository->getDocumentManager()->persist($product);
        } else {
            $this->productRepository->getDocumentManager()->remove($product);
        }
    }
}
