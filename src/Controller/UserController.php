<?php

namespace App\Controller;

use App\Document\User;
use App\Form\UserType;
use App\Form\UserUpdateType;
use App\Repository\UserRepository;
use App\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
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

    #[Route("/add", name: "addUser", methods: ["POST"])]
    public function addUser(Request $request): ?JsonResponse
    {
        $data = (object) json_decode($request->getContent(), true);
        $user = new User();

        $form = $this->createForm(UserType::class, $user);
        //$form->submit($request->request->get($form->getName()));

        if ($form->isSubmitted() && $form->isValid())
        {
            $user->setName($data->name);
            $user->setDocument($data->document);
            $user->setPhone($data->phone);
            $user->setPassword(password_hash($data->password, PASSWORD_BCRYPT));
            $user->setEmail($data->email);
            $user->setAddress($data->address);

            $this->userRepository->addUser($user, $this->documentManager);

            return $this->json(['message' => 'Usuario agregado correctamente']);
        }

        $errors = $form->getErrors(true)->count();

        return $this->json(['error' => $errors], 400);
    }

    #[Route("/update/{id}", name: "updateUser", methods: ["PATCH"])]
    public function updateUser($id, Request $request,): JsonResponse
    {
        $data = (object) json_decode($request->getContent(), true);
        $user = $this->userRepository->findById($id, $this->documentManager);

        if (!$user)
        {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        $form = $this->createForm
        (
            UserUpdateType::class,
            $user,
            ['validation_groups' => ['update']]
        );

        $form->submit($request->request->get($form->getName()), false);

        if ($form->isSubmitted() && $form->isValid())
        {
            if($this->userRepository->updateUser($data, $user, $this->documentManager))
            {
                return $this->json(['message' => 'Usuario actualizado correctamente'], 200);
            }
            else
            {
                return $this->json(['error' => 'No se ha podido actualizar'], 200);
            }
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], 400);
    }

    #[Route("/update/password/{id}", name: "updatePassword", methods: ["PATCH"])]
    public function changePassword
    (
        $id,
        Request $request,
        DocumentManager $documentManager
    ): JsonResponse
    {
        $data = (object) json_decode($request->getContent(), true);
        $user = $this->userRepository->findById($id, $documentManager);

        if (!$user)
        {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        $form = $this->createForm
        (
            UserUpdateType::class,
            $user,
            ['validation_groups' => ['update']]
        );

        $form->submit($request->request->get($form->getName()), false);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->userRepository->updateUser($data, $user, $this->documentManager);

            return $this->json(['message' => 'Contraseña actualizada correctamente'], 200);
        }

        $errors = $form->getErrors(true);

        return $this->json(['error' => $errors], 400);
    }


}