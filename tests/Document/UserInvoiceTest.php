<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\UserInvoice;
use PHPUnit\Framework\TestCase;

class UserInvoiceTest extends TestCase
{
    private UserInvoice $userInvoice;

    public function testGetName(): void
    {
        self::assertSame('Juan', $this->userInvoice->getName());
    }

    public function testSetName(): void
    {
        $name = 'Juan';

        self::assertSame($this->userInvoice, $this->userInvoice->setName($name));
        self::assertSame($name, $this->userInvoice->getName());
    }

    public function testGetDocument(): void
    {
        self::assertSame('100090', $this->userInvoice->getDocument());
    }

    public function testSetDocument(): void
    {
        $document = '100090';

        self::assertSame($this->userInvoice, $this->userInvoice->setDocument($document));
        self::assertSame($document, $this->userInvoice->getDocument());
    }

    public function testGetAddress(): void
    {
        self::assertSame('Calle falsa', $this->userInvoice->getAddress());
    }

    public function testSetAddress(): void
    {
        $address = 'Calle falsa';

        self::assertSame($this->userInvoice, $this->userInvoice->setAddress($address));
        self::assertSame($address, $this->userInvoice->getAddress());
    }

    public function testGetRol(): void
    {
        self::assertSame('ADMIN', $this->userInvoice->getRol());
    }

    public function testSetRol(): void
    {
        $rol = 'ADMIN';

        self::assertSame($this->userInvoice, $this->userInvoice->setRol($rol));
        self::assertSame($rol, $this->userInvoice->getRol());
    }

    public function testGetPhone(): void
    {
        self::assertSame('30050', $this->userInvoice->getPhone());
    }

    public function testSetPhone(): void
    {
        $phone = '30050';

        self::assertSame($this->userInvoice, $this->userInvoice->setPhone($phone));
        self::assertSame($phone, $this->userInvoice->getPhone());
    }

    public function testGetEmail(): void
    {
        self::assertSame('admin@gopenux.com', $this->userInvoice->getEmail());
    }

    public function testSetEmail(): void
    {
        $email = 'admin@gopenux.com';

        self::assertSame($this->userInvoice, $this->userInvoice->setEmail($email));
        self::assertSame($email, $this->userInvoice->getEmail());
    }

    public function testGetPassword(): void
    {
        self::assertSame('$2a$12$4XwkJK1f4s2BCAkMi6vKs.LD.nBNUO98suGM5JVXceDtEdMFnrrGK', $this->userInvoice->getPassword());
    }

    public function testSetPassword(): void
    {
        $password = '$2a$12$4XwkJK1f4s2BCAkMi6vKs.LD.nBNUO98suGM5JVXceDtEdMFnrrGK';

        self::assertSame($this->userInvoice, $this->userInvoice->setPassword($password));
        self::assertSame($password, $this->userInvoice->getPassword());
    }

    protected function setUp(): void
    {
        //parent::setUp();
        $this->userInvoice = (new UserInvoice())
            ->setName('Juan')
            ->setDocument('100090')
            ->setAddress('Calle falsa')
            ->setRol('ADMIN')
            ->setPhone('30050')
            ->setEmail('admin@gopenux.com')
            ->setPassword('$2a$12$4XwkJK1f4s2BCAkMi6vKs.LD.nBNUO98suGM5JVXceDtEdMFnrrGK');
    }

    protected function tearDown(): void
    {
        unset($this->product);
    }
}
