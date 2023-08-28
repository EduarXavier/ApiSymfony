<?php

namespace App\Repository;

use App\Document\Invoice;
use Doctrine\ODM\MongoDB\DocumentManager;

interface InvoicesRepositoryInterface
{
    public function findByDocumentAndStatus
    (
        string $document,
        string $status,
        DocumentManager $documentManager
    );

    public function findById
    (
        string $document,
        DocumentManager $documentManager
    );

    public function AddProductsToshoppingCart
    (
        array $products,
        string $document,
        DocumentManager $documentManager
    );

    public function createInvoice
    (
        Invoice $invoices,
        DocumentManager $documentManager
    );

    public function updateShoppingCart
    (
        array $products,
        string $document,
        DocumentManager $documentManager
    );

    public function  payInvoice
    (
        Invoice $invoice,
        DocumentManager $documentManager
    );
}
