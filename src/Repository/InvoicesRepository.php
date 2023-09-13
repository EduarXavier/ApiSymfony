<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Invoice;
use App\Document\UserInvoice;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

class InvoicesRepository extends ServiceDocumentRepository
{
    public function findAllByUser(UserInvoice $user): array
    {
        return $this->findBy(["user.document" => $user->getDocument()], ['date' => 'DESC'], limit: 20);
    }

    public function findAllForStatus(UserInvoice $user, string $status): array
    {
        return $this->findBy(["user.document" => $user->getDocument(), "status" => $status], ['date' => 'DESC'], limit: 20);
    }

    public function findById(string $id, string $status)
    {
        return $status ? $this->findOneBy(["id" => $id]) : $this->findOneBy(["id" => $id, "status" => $status]);
    }

    public function findByCode(string $code)
    {
        return $this->findOneBy(["code" => $code]);
    }

    public function findByDocumentAndStatus(string $document, string $status): ?Invoice
    {
        return $this->findOneBy(["user.document" => $document, "status" => $status]) ?? null;
    }

}
