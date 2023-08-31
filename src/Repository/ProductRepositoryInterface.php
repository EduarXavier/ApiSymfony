<?php

namespace App\Repository;

use App\Document\Product;
use Doctrine\ODM\MongoDB\DocumentManager;

interface ProductRepositoryInterface
{
    public function  findAll(DocumentManager $documentManager): ?array;
    public function  findById(string $id, DocumentManager $documentManager): ?Product;
    public function  addProduct(Product $product, DocumentManager $documentManager): ?string;
    public function  updateProduct(Product $product, DocumentManager $documentManager): ?string;
    public function  deleteProduct(Product $product, DocumentManager $documentManager): ?bool;

}
