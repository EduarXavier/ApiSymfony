<?php

namespace App\Repository;

use App\Document\Users;
use Doctrine\ODM\MongoDB\DocumentManager;

interface UserRepositoryInterface
{
    public function findByEmail(string $email, DocumentManager $documentManager);
    public function findById(string $id, DocumentManager $documentManager);
    public function addUser(Users $user, DocumentManager $documentManager);
    public function updateUser(object $data, Users $user, DocumentManager $documentManager);
}