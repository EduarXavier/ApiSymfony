<?php

namespace App\Repository;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;

class UserRepository implements UserRepositoryInterface
{

    public function findByEmail(string $email, DocumentManager $documentManager): ?User
    {
        $repository = $documentManager->getRepository(User::class);
        $user = $repository->findOneBy(["email" => $email]);

        return $user;
    }

    public function findById(string $id, DocumentManager $documentManager): ?User
    {
        $user = $documentManager->getRepository(User::class)->find($id);

        return $user;
    }

    public function findByDocument(string $document, DocumentManager $documentManager): ?User
    {
        $repository = $documentManager->getRepository(User::class);

        return $repository->findOneBy(["document" => $document]);
    }

    /**
     * @throws MongoDBException
     */
    public function addUser(User $user, DocumentManager $documentManager): bool
    {
        $documentManager->persist($user);
        $documentManager->flush();

        return true;
    }

    public function updateUser(User $user, DocumentManager $documentManager, string|null $method): bool
    {

        if($method == "password")
        {
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
        }

        $documentManager->flush();
        return true;
    }
}