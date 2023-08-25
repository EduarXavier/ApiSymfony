<?php

namespace App\Repository;

use App\Document\Users;
use Doctrine\ODM\MongoDB\DocumentManager;

class UserRepository implements UserRepositoryInterface
{

    public function findByEmail(string $email, DocumentManager $documentManager): ?Users
    {
        $repository = $documentManager->getRepository(Users::class);
        $user = $repository->findOneBy(["email" => $email]);

        return $user;
    }

    public function findById(string $id, DocumentManager $documentManager): ?Users
    {
        $user = $documentManager->getRepository(Users::class)->find($id);

        return $user;
    }

    public function addUser(Users $user, DocumentManager $documentManager): bool
    {
        $documentManager->persist($user);
        $documentManager->flush();

        return true;
    }

    public function updateUser(object $data, Users $user, DocumentManager $documentManager): bool
    {

        if($data->password)
        {
            $user->setPassword(password_hash($data->password, PASSWORD_BCRYPT));
        }
        else
        {
            $user->setAddress($data->address ?? $user->getAddress());
            $user->setPhone($data->phone ?? $user->getPhone());
        }
        $documentManager->flush();
        return true;
    }
}