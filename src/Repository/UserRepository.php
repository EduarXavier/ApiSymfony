<?php

namespace App\Repository;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email, DocumentManager $documentManager): ?User
    {
        $repository = $documentManager->getRepository(User::class);

        return $repository->findOneBy(['email' => $email]);
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function findById(string $id, DocumentManager $documentManager): ?User
    {
        return $documentManager->getRepository(User::class)->find($id);
    }

    public function findByDocument(string $document, DocumentManager $documentManager): ?User
    {
        $repository = $documentManager->getRepository(User::class);

        return $repository->findOneBy(['document' => $document]);
    }

    /**
     * @throws MongoDBException
     */
    public function addUser(User $user, DocumentManager $documentManager): bool
    {
        $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
        $documentManager->persist($user);
        $documentManager->flush();

        return true;
    }

    /**
     * @throws MongoDBException
     */
    public function updateUser(User $user, DocumentManager $documentManager, string | null $method): bool
    {
        if ($method == 'password') {
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
        }

        $documentManager->flush();

        return true;
    }
}
