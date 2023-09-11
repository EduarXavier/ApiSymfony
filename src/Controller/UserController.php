<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\User;
use App\Form\PasswordUpdateType;
use App\Form\UserType;
use App\Form\UserUpdateType;
use App\Repository\UserRepository;
use App\Services\EmailService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/user')]
class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private EmailService $emailService;


    public function __construct(UserRepository $userRepository, EmailService $emailService)
    {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }


    /**
     * @throws MongoDBException
     * @throws TransportExceptionInterface
     */
    #[Route('/add', name: 'addUser', methods: ['POST'])]
    public function addUser(Request $request): ?JsonResponse
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['method' => 'POST']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setName(ucfirst($user->getName()));
            $this->userRepository->addUser($user);
            $this->emailService->sendEmail($user, 'registro');

            return $this->json(['message' => 'Usuario agregado correctamente'], Response::HTTP_OK);
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
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

        if ($this->userRepository->updateUser($user, null)) {
            return $this->json(['message' => 'Usuario actualizado correctamente'], Response::HTTP_OK);
        }

        return $this->json(['error' => 'No se ha podido actualizar'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route('/api/update/password/{id}', name: 'updatePassword', methods: ['POST'])]
    public function changePassword($id, Request $request, DocumentManager $documentManager): JsonResponse
    {
        $user = $this->userRepository->findById($id, $documentManager);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(PasswordUpdateType::class, $user);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors(true);

            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->userRepository->updateUser($user, 'password');

        return $this->json(['message' => 'Contraseña actualizada correctamente'], Response::HTTP_BAD_REQUEST);
    }
}
