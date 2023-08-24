<?php

namespace App\Controller;

use App\Document\Users;
use App\Form\UserType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route("/user")]
class UserController extends AbstractController
{


     #[MongoDBException]

    #[Route("/add", name: "addUser", methods: ["POST"])]
    public function addUser
    (
        Request $request,
        DocumentManager $documentManager,
        LoggerInterface $logger,
    ): ?JsonResponse
    {
        $data = (object) json_decode($request->getContent(), true);
        $user = new Users();

        $form = $this->createForm(UserType::class, $user);

        $form->submit($request->request->get($form->getName()));

        if ($form->isSubmitted() && $form->isValid())
        {
            $user->setName($data->name);
            $user->setDocument($data->document);
            $user->setPhone($data->phone);
            $user->setPassword(password_hash($data->password, PASSWORD_BCRYPT));
            $user->setEmail($data->email);
            $user->setAddress($data->address);

            $documentManager->persist($user);
            $documentManager->flush();


            return $this->json(['message' => 'Usuario agregado correctamente']);
        }

        $errors = $form->getErrors(true,);
        return $this->json(['error' => $errors], 400);
    }

    #[Route("/update/{id}", name: "updateUser", methods: ["PATCH"])]
    public function updateUser(
        $id,
        Request $request,
        DocumentManager $documentManager,
        LoggerInterface $logger
    ): JsonResponse
    {
        $user = $documentManager->getRepository(Users::class)->find($id);

        if (!$user)
        {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(UserType::class, $user);
        $form->submit($data, false);

        if ($form->isSubmitted() && $form->isValid())
        {
            $documentManager->flush();

            return $this->json(['message' => 'Usuario actualizado correctamente']);
        }

        $errors = $form->getErrors(true);
        return $this->json(['error' => $errors], 400);
    }

}