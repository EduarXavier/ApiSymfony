<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    public function testGetName(): void
    {
        self::assertSame('Juan', $this->user->getName());
    }

    public function testSetName(): void
    {
        $name = 'Juan';

        self::assertSame($this->user, $this->user->setName($name));
        self::assertSame($name, $this->user->getName());
    }

    public function testGetDocument(): void
    {
        self::assertSame('100090', $this->user->getDocument());
    }

    public function testSetDocument(): void
    {
        $document= '100090';

        self::assertSame($this->user, $this->user->setDocument($document));
        self::assertSame($document, $this->user->getDocument());
    }

    public function testGetAddress(): void
    {
        self::assertSame('Calle falsa', $this->user->getAddress());
    }

    public function testSetAddress(): void
    {
        $address = 'Calle falsa';

        self::assertSame($this->user, $this->user->setAddress($address));
        self::assertSame($address, $this->user->getAddress());
    }

    public function testGetRol(): void
    {
        self::assertSame('ADMIN', $this->user->getRol());
    }

    public function testSetRol(): void
    {
        $rol = 'ADMIN';

        self::assertSame($this->user, $this->user->setRol($rol));
        self::assertSame($rol, $this->user->getRol());
    }

    public function testGetPhone(): void
    {
        self::assertSame('30050', $this->user->getPhone());
    }

    public function testSetPhone(): void
    {
        $phone = '30050';

        self::assertSame($this->user, $this->user->setPhone($phone));
        self::assertSame($phone, $this->user->getPhone());
    }

    public function testGetEmail(): void
    {
        self::assertSame('admin@gopenux.com', $this->user->getEmail());
    }

    public function testSetEmail(): void
    {
        $email = 'admin@gopenux.com';

        self::assertSame($this->user, $this->user->setEmail($email));
        self::assertSame($email, $this->user->getEmail());
    }

    public function testGetPassword(): void
    {
        self::assertSame('$2a$12$4XwkJK1f4s2BCAkMi6vKs.LD.nBNUO98suGM5JVXceDtEdMFnrrGK', $this->user->getPassword());
    }

    public function testSetPassword(): void
    {
        $password = '$2a$12$4XwkJK1f4s2BCAkMi6vKs.LD.nBNUO98suGM5JVXceDtEdMFnrrGK';

        self::assertSame($this->user, $this->user->setPassword($password));
        self::assertSame($password, $this->user->getPassword());
    }

    protected function setUp(): void
    {
        //parent::setUp();
        $this->user = (new User())
            ->setName('Juan')
            ->setDocument('100090')
            ->setAddress('Calle falsa')
            ->setRol('ADMIN')
            ->setPhone('30050')
            ->setEmail('admin@gopenux.com')
            ->setPassword('$2a$12$4XwkJK1f4s2BCAkMi6vKs.LD.nBNUO98suGM5JVXceDtEdMFnrrGK')
        ;
    }

    protected function tearDown(): void
    {
        unset($this->user);
    }

}