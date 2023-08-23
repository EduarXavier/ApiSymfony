<?php

namespace App\Controller;

use App\Document\Users;
use App\Form\UserType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Route("/usuario", name: "addUser", methods: ["POST"])]
    public function addUser(Request $request, DocumentManager $documentManager): JsonResponse
    {
        $user = new Users();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $documentManager->persist($user);
                $documentManager->flush();

                return new JsonResponse(['message' => 'Usuario agregado correctamente']);

            }
            catch (MongoDBException $error){

                return new JsonResponse(['Error' => $error], 500);

            }

        }

        $errors = $form->getErrors(true);

        return new JsonResponse(['errors' => $errors], 400);

    }


}