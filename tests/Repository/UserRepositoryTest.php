<?php

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
    private DocumentManager $documentManager;
    private User $user;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->documentManager = self::getContainer()->get(DocumentManager::class);
        $this->user = (new User())
            ->setEmail('user@test.com')
            ->setName('juanito')
            ->setDocument('1090002')
        ;
        $this->documentManager->persist($this->user);
        $this->documentManager->flush();
    }

    protected function tearDown(): void
    {
        $this->documentManager->getSchemaManager()->dropDatabases();

        unset($this->user);
        unset($this->userRepository);
        unset($this->documentManager);
    }

    public function testFindByEmail(): void
    {
        $foundUser = $this->userRepository->findByEmail($this->user->getEmail());

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->getEmail(), $foundUser->getEmail());
    }

    public function testFindByDocument(): void
    {
        $foundUser = $this->userRepository->findByDocument($this->user->getDocument());

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->getDocument(), $foundUser->getDocument());
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function testFindById(): void
    {
        $foundUser = $this->userRepository->findById($this->user->getId());

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->getId(), $foundUser->getId());
    }
}