<?php

namespace App\Tests\Document;

use App\Document\MessageQueue;
use App\Document\User;
use Monolog\Test\TestCase;
use PHPUnit\Framework\MockObject\Exception;

class MessageQueueTest extends TestCase
{
    private MessageQueue $messageQueue;
    private User $user;

    /**
     * @throws Exception
     */
    public function testGetId()
    {
        $id = 'df347937ed8a3a5d948e81dc3b45d6e6';

        $messageQueue = $this->createConfiguredMock(
            MessageQueue::class,
            [
                'getId' => $id,
            ]
        );

        self::assertEquals($id, $messageQueue->getId());
    }

    public function testGetType()
    {
        self::assertEquals('First-shop', $this->messageQueue->getType());
    }

    public function testSetType()
    {
        $type = 'registro';
        $this->messageQueue->setType($type);

        self::assertEquals($type, $this->messageQueue->getType());
    }

    public function testGetProcessed()
    {
        self::assertFalse($this->messageQueue->getProcessed());
    }

    public function testSetProcessed()
    {
        $this->messageQueue->setProcessed(true);

        self::assertTrue($this->messageQueue->getProcessed());
    }

    public function testGetRejected()
    {
        self::assertFalse($this->messageQueue->getRejected());
    }

    public function testSetRejected()
    {
        $this->messageQueue->setRejected(true);

        self::assertTrue($this->messageQueue->getRejected());
    }

    public function testGetUser()
    {
        self::assertSame($this->user, $this->messageQueue->getUser());
    }

    public function testSetUser()
    {
        $user = (new User())->setName('User test 2');
        $this->messageQueue->setUser($user);

        self::assertSame($user, $this->messageQueue->getUser());
    }

    protected function setUp(): void
    {
        $this->user = (new User())->setName('User test');

        $this->messageQueue = (new MessageQueue())
            ->setProcessed(false)
            ->setRejected(false)
            ->setType('First-shop')
            ->setUser($this->user);
    }

    public function tearDown(): void
    {
        unset(
            $this->user,
            $this->messageQueue
        );
    }
}