<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Invoice;
use App\Document\Product;
use App\Document\User;
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
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry, $documentClass, ProductRepository $productRepository)
    {
        parent::__construct($registry, $documentClass);
        $this->documentManager = $this->getDocumentManager();
    }

    public function findAllByUser(User $user): array
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findBy(["user.document" => $user->getDocument()], ['date' => 'DESC'], limit: 20);
    }

    public function findAllForStatus(User $user, string $status): array
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findBy(["user.document" => $user->getDocument(), "status" => $status], ['date' => 'DESC'], limit: 20);
    }

    public function findById(string $id, string $status)
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $status ? $repository->findOneBy(["id" => $id]) : $repository->findOneBy(["id" => $id, "status" => $status]);
    }

    public function findByCode(string $code)
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findOneBy(["code" => $code]);
    }

    public function findByDocumentAndStatus(string $document, string $status): ?Invoice
    {
        $repository = $this->documentManager->getRepository(Invoice::class);

        return $repository->findOneBy(["user.document" => $document, "status" => $status]);
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

        return $this->documentManager;
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

        return $this->documentManager;
    }

}
