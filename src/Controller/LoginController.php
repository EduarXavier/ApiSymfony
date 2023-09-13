<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\User;
use App\Document\UserInvoice;
use App\Form\LoginType;
use App\Repository\UserRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

class LoginController extends AbstractController
{
    private UserRepository $userRepository;
    private SerializerInterface $serializer;


    public function __construct
    (
        UserRepository $userRepository,
        SerializerInterface $serializer
    )
    {
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
    }

    #[Route('/login-view', name: 'login_template')]
    public function loginView(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(LoginType::class, $user);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('UserTemplate/login.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $userFind = $this->userRepository->findByEmail($user->getEmail());

        if ($userFind && password_verify($user->getPassword(), $userFind->getPassword())) {
            $session = $request->getSession();

            $userJson = $this->serializer->serialize($userFind, "json");
            $userSession = $this->serializer->deserialize($userJson, UserInvoice::class, "json");
            $session->set('user', $userSession);
            $session->set('email', $user->getEmail());
            $session->set('rol', $userFind->getRol());
            $session->set('document', $userFind->getDocument());
            $session->set('shopping-cart', array());

            $this->addFlash('message', 'Bienvenido ' . $session->get('user')->getName());
            return $this->render('UserTemplate/dashboard.html.twig', []);
        }

        $this->addFlash('message', 'Credenciales invalidas');

        return $this->render('UserTemplate/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $session->clear();

        return $this->redirectToRoute('login_template');
    }
}
