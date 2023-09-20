<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Document\User;
use App\Repository\UserRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $userRepository;
    private User $user;

    public function testFindByEmail(): void
    {
        $foundUser = $this->userRepository->findByEmail($this->user->getEmail());

        self::assertInstanceOf(User::class, $foundUser);
        self::assertEquals($this->user->getEmail(), $foundUser->getEmail());
    }

    public function testFindByDocument(): void
    {
        $foundUser = $this->userRepository->findByDocument($this->user->getDocument());

        self::assertInstanceOf(User::class, $foundUser);
        self::assertEquals($this->user->getDocument(), $foundUser->getDocument());
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function testFindById(): void
    {
        $foundUser = $this->userRepository->findById($this->user->getId());

        self::assertInstanceOf(User::class, $foundUser);
        self::assertEquals($this->user->getId(), $foundUser->getId());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->user = (new User())
            ->setEmail('user@test.com')
            ->setName('juanito')
            ->setDocument('1090002')
        ;
        $this->userRepository->getDocumentManager()->persist($this->user);
        $this->userRepository->getDocumentManager()->flush();
    }

    protected function tearDown(): void
    {
        $this->userRepository->getDocumentManager()->getSchemaManager()->dropDatabases();

        unset(
            $this->user,
            $this->userRepository,
            $this->documentManager
        );
    }

}