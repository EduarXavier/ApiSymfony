<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\User;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;

class UserRepository extends ServiceDocumentRepository
{
    public function findByEmail(string $email): ?User
    {
        $repository = $this->getDocumentManager()->getRepository(User::class);

        return $repository->findOneBy(['email' => $email]);
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function findById(string $id): ?User
    {
        return$this->getDocumentManager()->getRepository(User::class)->find($id);
    }

    public function findByDocument(string $document): ?User
    {
        $repository = $this->getDocumentManager()->getRepository(User::class);

        return $repository->findOneBy(['document' => $document]);
    }

    /**
     * @throws MongoDBException
     */
    public function addUser(User $user): bool
    {
        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        return true;
    }

    /**
     * @throws MongoDBException
     */
    public function updateUser(User $user, string | null $method): bool
    {
        if ($method == 'password') {
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
        }

        $this->getDocumentManager()->flush();

        return true;
    }
}
