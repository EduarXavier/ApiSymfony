<?php

declare(strict_types=1);

namespace App\Transport;

use App\Document\MessageQueue;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

class MongoDbTransport implements TransportInterface
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @throws MongoDBException
     */
    public function get(): iterable
    {
        $messages = $this->documentManager
            ->getRepository(MessageQueue::class)
            ->findBy(['processed' => false], null, 10);
        $envelopes  = array();

        foreach ($messages as $message) {
            $envelopes[] = new Envelope($message);
            $this->documentManager->remove($message);
            $this->documentManager->flush();
        }

        return $envelopes;
    }

    /**
     * @throws MongoDBException
     */
    public function ack(Envelope $envelope): void
    {
        $message = $envelope->getMessage();
        $message->setProcessed(true);

        $this->documentManager->flush();
    }

    /**
     * @throws MongoDBException
     */
    public function reject(Envelope $envelope): void
    {
        $message = $envelope->getMessage();
        $message->setRejected(true);
        $message->setProcessed(true);

        $this->documentManager->flush();
    }

    /**
     * @throws MongoDBException
     */
    public function send(Envelope $envelope): Envelope
    {
        $message = $envelope->getMessage();

        $this->documentManager->persist($message);
        $this->documentManager->flush();

        return $envelope;
    }
}
