<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\ProductInvoice;
use App\Document\User;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

class InvoicesRepository extends ServiceDocumentRepository
{
    public const CANT_MAX_INVOICE = 8;

    public function findAllByUser(User $user, int $offset): array
    {
        return $this->findBy(['user.id' => $user->getId()], ['date' => 'DESC'], self::CANT_MAX_INVOICE, $offset);
    }

    public function findNotCancelByUser(User $user): array
    {
        return $this->findBy(['user.id' => $user->getId(), 'status' => ['$ne' => Invoice::CANCEL]], ['date' => 'DESC']);
    }

    public function findAllForStatus(User $user, string $status, int $offset): array
    {
        return $this->findBy(['user.id' => $user->getId(), 'status' => $status], ['date' => 'DESC'], self::CANT_MAX_INVOICE, $offset);
    }

    public function findByIdAndStatus(string $id, string $status): ?Invoice
    {
        return $this->findOneBy(['id' => $id, 'status' => $status]);
    }

    public function findById(string $id): ?Invoice
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByCode(string $code)
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findByUserAndStatus(User $user, string $status): ?Invoice
    {
        return $this->findOneBy(['user.id' => $user->getId(), 'status' => $status]);
    }

    public function findByProduct(ProductInvoice $product): array
    {
        return $this->findBy(['products.code' => $product->getCode()], ['date' => 'DESC']);
    }
}
