<?php

declare(strict_types=1);

namespace App\Services;

use App\Document\User;
use App\Repository\UserRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class UserService
{
    private UserRepository $userRepository;

    public function __construct (
        UserRepository $userRepository,
    ) {
        $this->userRepository = $userRepository;
    }

    public function addUser(User $user): void
    {
        $this->userRepository->getDocumentManager()->persist($user);
    }

    public function updateUser(User $user, string | null $method): void
    {
        if ($method == 'password') {
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
        }
    }
}
