<?php

namespace App\Repository;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;

interface UserRepositoryInterface
{
    public function findByEmail(string $email, DocumentManager $documentManager);
    public function findById(string $id, DocumentManager $documentManager);
    public function findByDocument(string $document, DocumentManager $documentManager);
    public function addUser(User $user, DocumentManager $documentManager);
    public function updateUser(User $user, DocumentManager $documentManager, string|null $method);
}