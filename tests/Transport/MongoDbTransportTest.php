<?php

declare(strict_types=1);

namespace App\Tests\Transport;

use App\Document\MessageQueue;
use App\Document\User;
use App\Transport\MongoDbTransport;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;

class MongoDbTransportTest extends KernelTestCase
{
    private DocumentManager $documentManager;
    private MongoDbTransport $mongoDbTransport;
    private MessageQueue $messageQueue;

    /**
     * @throws MongoDBException
     */
    public function testGet()
    {
        $messages = $this->mongoDbTransport->get();

        self::assertIsArray($messages);
        self::assertCount(1, $messages);
    }

    /**
     * @throws MongoDBException
     */
    public function testAck()
    {
        $envelope = new Envelope($this->messageQueue);
        $this->mongoDbTransport->ack($envelope);

        self::assertTrue($this->messageQueue->getProcessed());
    }

    /**
     * @throws MongoDBException
     */
    public function testReject()
    {
        $envelope = new Envelope($this->messageQueue);
        $this->mongoDbTransport->reject($envelope);

        self::assertTrue($this->messageQueue->getRejected());
    }

    /**
     * @throws MongoDBException
     */
    public function testSend()
    {
        $envelope = new Envelope($this->messageQueue);
        $response = $this->mongoDbTransport->send($envelope);

        self::assertInstanceOf(Envelope::class, $response);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->documentManager = $this->getContainer()->get(DocumentManager::class);
        $this->mongoDbTransport = new MongoDbTransport($this->documentManager);
        $user = (new User())->setName('User test');
        $this->documentManager->persist($user);
        $this->documentManager->flush();
        $this->messageQueue = (new MessageQueue())
            ->setProcessed(false)
            ->setRejected(false)
            ->setType('first-shop')
            ->setUser($user);
        $this->documentManager->persist($this->messageQueue);
        $this->documentManager->flush();
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        $this->documentManager = $this->getContainer()->get(DocumentManager::class);
        $this->documentManager->getSchemaManager()->dropDatabases();
        unset(
            $this->mongoDbTransport,
            $this->messageQueue,
            $this->documentManager
        );
    }
}
