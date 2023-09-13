<?php

declare(strict_types=1);

namespace App\Managers;

use App\Document\User;
use App\Repository\UserRepository;
use Symfony\Contracts\Service\Attribute\Required;

class UserManager
{
    private UserRepository $userRepository;

    public function __construct (
        UserRepository $userRepository,
    ) {
        $this->userRepository = $userRepository;
    }

    #[Required]
    public function setUserRepository(UserRepository $userRepository): void
    {
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
