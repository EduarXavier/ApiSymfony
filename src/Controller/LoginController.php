<?php

namespace App\Controller;

use App\Document\User;
use App\Form\LoginType;
use App\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LoginController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private DocumentManager $documentManager;

    public function __construct
    (
        DocumentManager $documentManager,
        UserRepositoryInterface $userRepository
    )
    {
        $this->documentManager = $documentManager;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws JWTEncodeFailureException
     */
    #[Route("/login", name: "login", methods: ["POST"])]
    public function login(
        Request               $request,
        UserProviderInterface $userProvider,
        JWTEncoderInterface   $encoder
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true,);
        $user = new User();

        $form = $this->createForm(LoginType::class, $user);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid())
        {
            $user->setEmail($data["email"]);
            $user->setPassword($data["password"]);
            $userFind = $this->userRepository->findByEmail($user->getEmail(), $this->documentManager);

            if ($userFind && password_verify($user->getPassword(), $userFind->getPassword()))
            {
                $token = $encoder->encode(['email' => $user->getEmail()]);

                return $this->json(['token' => $token]);
            }

            return $this->json(["Error" => "Credenciales Inválidas"], 400);
        }

        return $this->json(["Error" => "Datos de formulario inválidos"], 400);
    }
}
