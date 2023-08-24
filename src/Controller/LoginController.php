<?php

namespace App\Controller;

use App\Document\Users;
use Doctrine\ODM\MongoDB\DocumentManager;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LoginController extends AbstractController
{
    #[Route("/login", name: "login", methods: ["POST"])]
    public function login
    (
        Request $request,
        UserProviderInterface $userProvider,
        JWTEncoderInterface $encoder,
        DocumentManager $documentManager
    ): ?JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Valida las credenciales del usuario
        $username = $data['email'];
        $password = $data['password'];

        $repository = $documentManager->getRepository(Users::class);
        $user = $repository->findOneBy(["email" => $username]);

        if(password_verify($password ,$user->getPassword()))
        {

            $token = $encoder->encode(['email' => $user->getEmail()]);

            return $this->json(['token' => $token]);
        }
        else
        {
            throw new BadCredentialsException('Credenciales inválidas');
        }

    }

}