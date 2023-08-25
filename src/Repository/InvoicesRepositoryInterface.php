<?php

namespace App\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;

interface InvoicesRepositoryInterface
{
    public function AddProductsToshoppingCart
    (
        array $products,
        string $document,
        DocumentManager $documentManager)
    ;

    public function updateShoppingCart
    (
        array $products,
        string $document,
        DocumentManager $documentManager
    );
}