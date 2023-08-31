<?php

namespace App\Repository;

use App\Document\Invoice;
use Doctrine\ODM\MongoDB\DocumentManager;

interface InvoicesRepositoryInterface
{

    public function findAll(DocumentManager $documentManager);

    public function findByDocumentAndStatus(string $document, string $status, DocumentManager $documentManager);

    public function findById(string $document, DocumentManager $documentManager);

    public function AddProductsToShoppingCart(array $products, string $document, DocumentManager $documentManager);

    public function createInvoice(Invoice $invoices, DocumentManager $documentManager);

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

    public function  deleteInvoice
    (
        Invoice $invoice,
        DocumentManager $documentManager
    );

    public function deleteShoppingCart
    (
        Invoice $shoppingCart,
        DocumentManager $documentManager
    );

    public function deleteProductToShoppingCart
    (
        string $document,
        string $idProduct,
        DocumentManager $documentManager
    );
}
