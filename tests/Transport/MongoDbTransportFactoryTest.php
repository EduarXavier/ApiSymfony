<?php

namespace App\Tests\Transport;

use App\Transport\MongoDbTransportFactory;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\TransportInterface;

class MongoDbTransportFactoryTest extends KernelTestCase
{
    private MongoDbTransportFactory $mongoDbTransportFactory;
    private string $dsn;

    public function testCreateTransport()
    {
        $response = $this->mongoDbTransportFactory->createTransport($this->dsn, [], new Serializer());

        self::assertInstanceOf(TransportInterface::class, $response);
    }

    public function testSupports()
    {
        $response = $this->mongoDbTransportFactory->supports($this->dsn, []);

        self::assertTrue($response);
    }

    public function testSupportsWhitBadDsn()
    {
        $response = $this->mongoDbTransportFactory->supports('sync://', []);

        self::assertFalse($response);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $documentManager = $this->getContainer()->get(DocumentManager::class);
        $this->mongoDbTransportFactory = new MongoDbTransportFactory($documentManager);
        $this->dsn = 'mongodb://';
    }

    protected function tearDown(): void
    {
        unset($this->mongoDbTransportFactory);
    }
}
