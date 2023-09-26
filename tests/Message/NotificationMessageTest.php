<?php

namespace App\Tests\Message;

use App\Document\User;
use App\Message\NotificationMessage;
use PHPUnit\Framework\TestCase;

class NotificationMessageTest extends TestCase
{
    private NotificationMessage $message;

    public function testSetUser()
    {
        $user = (new User())->setName('user-test');
        $this->message->setUser($user);

        self::assertInstanceOf(User::class, $this->message->getUser());
        self::assertEquals($user, $this->message->getUser());
    }

    public function testGetUser()
    {
        self::assertInstanceOf(User::class, $this->message->getUser());
        self::assertEquals(new User(), $this->message->getUser());
    }

    public function testSetType()
    {
        $type = 'first-shop';
        $this->message->setType($type);

        self::assertIsString($this->message->getType());
        self::assertEquals($type, $this->message->getType());
    }

    public function testGetType()
    {
        self::assertIsString($this->message->getType());
        self::assertEquals('test-type', $this->message->getType());
    }

    protected function setUp(): void
    {
        $this->message = new NotificationMessage(new User(), 'test-type');
    }

    protected function tearDown(): void
    {
        unset($this->message);
    }
}