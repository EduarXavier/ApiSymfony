<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\User;
use App\Form\PasswordUpdateType;
use App\Form\UserType;
use App\Form\UserUpdateType;
use App\Managers\UserManager;
use App\Repository\UserRepository;
use App\Services\EmailService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\Attribute\Required;


#[Route('/user')]
class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private UserManager $userManager;
    private EmailService $emailService;
    private DocumentManager $documentManager;

    #[Required]
    public function setUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    #[Required]
    public function setUserManager(UserManager $userManager): void
    {
        $this->userManager = $userManager;
    }

    #[Required]
    public function setEmailService(EmailService $emailService): void
    {
        $this->emailService = $emailService;
    }

    #[Required]
    public function setDocumentManager(DocumentManager $documentManager): void
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @throws MongoDBException
     * @throws TransportExceptionInterface
     */
    #[Route('/api/add', name: 'addUser', methods: ['POST'])]
    public function addUser(Request $request, UserPasswordHasherInterface $passwordHasher): ?JsonResponse
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['method' => 'POST']);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors(true);

            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $user->setName(ucfirst($user->getName()));
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );
        $user->setPassword($hashedPassword);

        if ($this->userRepository->findByDocument($user->getDocument())) {
            return $this->json(['Error' => 'Ya existe un usuario con este documento'], Response::HTTP_BAD_REQUEST);
        } else if ($this->userRepository->findByEmail($user->getEmail())) {
            return $this->json(['Error' => 'Ya existe un usuario con este Email'], Response::HTTP_BAD_REQUEST);
        }

        $this->userManager->addUser($user);
        $this->emailService->sendEmail($user, 'registro');
        $this->documentManager->flush();

        return $this->json(['message' => 'Usuario agregado correctamente'], Response::HTTP_OK);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route('/api/update/{id}', name: 'updateUser', methods: ['POST'])]
    public function updateUser($id, Request $request): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(UserUpdateType::class, $user);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors(true);

            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->userManager->updateUser($user, null);
        $this->documentManager->flush();

        return $this->json(['message' => 'Usuario actualizado correctamente'], Response::HTTP_OK);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route('/api/update/password/{id}', name: 'updatePassword', methods: ['POST'])]
    public function changePassword($id, Request $request): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(PasswordUpdateType::class, $user);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors(true);

            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->userManager->updateUser($user, 'password');
        $this->documentManager->flush();

        return $this->json(['message' => 'Contraseña actualizada correctamente'], Response::HTTP_BAD_REQUEST);
    }
}
