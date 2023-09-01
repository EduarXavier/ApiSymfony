<?php

namespace App\Controller;

use App\Document\User;
use App\Form\UserType;
use App\Form\UserUpdateType;
use App\Repository\UserRepository;
use App\Repository\UserRepositoryInterface;
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
    private UserRepositoryInterface $userRepository;
    private DocumentManager $documentManager;
    private EmailController $emailController;

    public function __construct(DocumentManager $documentManager, EmailController $emailController)
    {
        $this->documentManager = $documentManager;
        $this->emailController = $emailController;
        $this->userRepository = new UserRepository();
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
            $this->userRepository->addUser($user, $this->documentManager);
            $this->emailController->sendEmail($user->getEmail(), 'registro');

            return $this->json(['message' => 'Usuario agregado correctamente'], Response::HTTP_OK);
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route('/update/{id}', name: 'updateUser', methods: ['POST'])]
    public function updateUser($id, Request $request): JsonResponse
    {
        $user = $this->userRepository->findById($id, $this->documentManager);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(UserUpdateType::class, $user, [
            'method' => 'POST',
            'validation_groups' => ['update'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->userRepository->updateUser($user, $this->documentManager, null)) {
                return $this->json(['message' => 'Usuario actualizado correctamente'], Response::HTTP_OK);
            } else {
                return $this->json(['error' => 'No se ha podido actualizar'], Response::HTTP_BAD_REQUEST);
            }
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route('/update/password/{id}', name: 'updatePassword', methods: ['POST'])]
    public function changePassword($id, Request $request, DocumentManager $documentManager): JsonResponse
    {
        $user = $this->userRepository->findById($id, $documentManager);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(UserUpdateType::class, $user, [
            'method' => 'POST',
            'validation_groups' => ['update'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->updateUser($user, $this->documentManager, 'password');

            return $this->json(['message' => 'Contraseña actualizada correctamente'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], 400);
    }
}
