<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\User;
use App\Document\UserInvoice;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class InvoicesRepository extends ServiceDocumentRepository
{
    public function findAllByUser(UserInvoice $user): array
    {
        $repository = $this->getDocumentManager()->getRepository(Invoice::class);

        return $repository->findBy(["user.document" => $user->getDocument()], ['date' => 'DESC'], limit: 20);
    }

    public function findAllForStatus(UserInvoice $user, string $status): array
    {
        $repository = $this->getDocumentManager()->getRepository(Invoice::class);

        return $repository->findBy(["user.document" => $user->getDocument(), "status" => $status], ['date' => 'DESC'], limit: 20);
    }

    public function findById(string $id, string $status)
    {
        $repository = $this->getDocumentManager()->getRepository(Invoice::class);

        return $status ? $repository->findOneBy(["id" => $id]) : $repository->findOneBy(["id" => $id, "status" => $status]);
    }

    public function findByCode(string $code)
    {
        $repository = $this->getDocumentManager()->getRepository(Invoice::class);

        return $repository->findOneBy(["code" => $code]);
    }

    public function findByDocumentAndStatus(string $document, string $status): ?Invoice
    {
        $repository = $this->getDocumentManager()->getRepository(Invoice::class);

        return $repository->findOneBy(["user.document" => $document, "status" => $status]) ?? null;
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function createInvoice(Invoice $invoice): DocumentManager
    {
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $invoice->setDate($fecha->format("Y-m-d H:i:s"));
        $invoice->setStatus("invoice");

        return $this->getDocumentManager();
    }

    /**
     * @throws MongoDBException
     * @throws Exception
     */
    public function payInvoice(Invoice $invoice): DocumentManager
    {
        $fecha = new DateTime('now', new DateTimeZone('America/Bogota'));
        $invoice->setDate($fecha->format("Y-m-d H:i:s"));
        $invoice->setStatus("pay");

        return $this->getDocumentManager();
    }

    public function deleteInvoice(Invoice $invoice): DocumentManager
    {
        $this->getDocumentManager()->persist($invoice);
        $this->getDocumentManager()->remove($invoice);

        return $this->getDocumentManager();
    }

}
