<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\User;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

class UserRepository extends ServiceDocumentRepository
{
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function findById(string $id): ?User
    {
        return $this->find($id);
    }

    public function findByDocument(string $document): ?User
    {
        return $this->findOneBy(['document' => $document]);
    }
}
