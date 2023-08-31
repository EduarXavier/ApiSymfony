<?php

namespace App\Controller;

use App\Document\User;
use App\Form\UserType;
use App\Form\UserUpdateType;
use App\Repository\UserRepository;
use App\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route("/user")]
class UserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->userRepository = new UserRepository();
    }

    /**
     * @throws MongoDBException
     */
    #[Route("/add", name: "addUser", methods: ["POST"])]
    public function addUser(Request $request): ?JsonResponse
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['method' => 'POST']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->addUser($user, $this->documentManager);

            return $this->json(['message' => 'Usuario agregado correctamente']);
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], 400);
    }

    #[Route("/update/{id}", name: "updateUser", methods: ["POST"])]
    public function updateUser($id, Request $request,): JsonResponse
    {
        $user = $this->userRepository->findById($id, $this->documentManager);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        $form = $this->createForm(UserUpdateType::class, $user, [
                'method' => 'POST',
                'validation_groups' => ['update'],
            ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($this->userRepository->updateUser($user, $this->documentManager, null)) {
                return $this->json(['message' => 'Usuario actualizado correctamente'], 200);
            }
            else {
                return $this->json(['error' => 'No se ha podido actualizar'], 200);
            }
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], 400);
    }

    #[Route("/update/password/{id}", name: "updatePassword", methods: ["POST"])]
    public function changePassword($id, Request $request, DocumentManager $documentManager): JsonResponse
    {
        $user = $this->userRepository->findById($id, $documentManager);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        $form = $this->createForm(UserUpdateType::class, $user, [
            'method' => 'POST',
            'validation_groups' => ['update'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->updateUser($user, $this->documentManager, "password");

            return $this->json(['message' => 'Contraseña actualizada correctamente'], 200);
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], 400);
    }
}
