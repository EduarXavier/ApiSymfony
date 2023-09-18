<?php

namespace App\Tests\Managers;

use App\Document\User;
use App\Managers\UserManager;
use App\Repository\UserRepository;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserManagerTest extends KernelTestCase
{
    private UserRepository $userRepository;
    private UserManager $userManager;
    private User $user;

    /**
     * @throws MongoDBException
     */
    public function testAddUser(): void
    {
        $this->userManager->addUser($this->user);
        $this->userRepository->getDocumentManager()->flush();
        $userFind = $this->userRepository->findByDocument($this->user->getDocument());

        self::assertInstanceOf(User::class, $userFind);
        self::assertSame($this->user, $userFind);
    }

    /**
     * @throws MongoDBException
     */
    public function testUpdateUser(): void
    {
        $address = 'Calle falsa 2';
        $phone = '3000';
        $this->userManager->addUser($this->user);
        $this->userRepository->getDocumentManager()->flush();
        $this->user->setPhone($phone);
        $this->user->setAddress($address);
        $this->userManager->updateUser($this->user, null);
        $this->userRepository->getDocumentManager()->flush();
        $userFind = $this->userRepository->findByDocument($this->user->getDocument());

        self::assertInstanceOf(User::class, $userFind);
        self::assertEquals($address, $this->user->getAddress());
        self::assertEquals($phone, $this->user->getPhone());
        self::assertSame($this->user, $userFind);
    }

    /**
     * @throws MongoDBException
     */
    public function testUpdatePasswordUser(): void
    {
        $password = 'password';
        $this->userManager->addUser($this->user);
        $this->userRepository->getDocumentManager()->flush();
        $this->user->setPassword($password);
        $this->userManager->updateUser($this->user, 'password');
        $this->userRepository->getDocumentManager()->flush();
        $userFind = $this->userRepository->findByDocument($this->user->getDocument());

        self::assertInstanceOf(User::class, $userFind);
        self::assertTrue(password_verify($password, $this->user->getPassword()));
        self::assertSame($this->user, $userFind);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->userManager = self::getContainer()->get(UserManager::class);
        $this->user = (new User())
            ->setEmail('user@test.com')
            ->setName('juanito')
            ->setDocument('1090002')
            ->setAddress('Calle flasa')
            ->setPhone('1000')
        ;
    }

    protected function tearDown(): void
    {
        $this->userRepository->getDocumentManager()->getSchemaManager()->dropDatabases();

        unset(
            $this->user,
            $this->userRepository,
            $this->userManager
        );
    }
}