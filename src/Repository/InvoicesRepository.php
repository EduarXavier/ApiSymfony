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
    public function findAllByUser(User $user): array
    {
        return $this->findBy(['user.id' => $user->getId()], ['date' => 'DESC'], limit: 20);
    }

    public function findNotCancelByUser(User $user): array
    {
        return $this->findBy(['user.id' => $user->getId(), 'status' => ['$ne' => Invoice::CANCEL]], ['date' => 'DESC'], limit: 20);
    }

    public function findAllForStatus(User $user, string $status): array
    {
        return $this->findBy(['user.id' => $user->getId(), 'status' => $status], ['date' => 'DESC'], limit: 20);
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
        return $this->findBy(['products.code' => $product->getCode()], ['date' => 'DESC'], limit: 20);
    }
}
