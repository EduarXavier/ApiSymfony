<?php

namespace App\Repository;

use App\Document\Products;
use Doctrine\ODM\MongoDB\DocumentManager;

interface ProductRepositoryInterface
{
    public function  findAll(DocumentManager $documentManager): ?array;
    public function  findByIf(string $id, DocumentManager $documentManager): ?Products;
    public function  addProduct(Products $product, DocumentManager $documentManager): ?Products;
    public function  updateProduct(Products $product, DocumentManager $documentManager): ?Products;
    public function  deleteProduct(string $id, DocumentManager $documentManager): ?Products;

}