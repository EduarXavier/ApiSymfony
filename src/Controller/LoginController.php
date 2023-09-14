<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\User;
use App\Document\UserInvoice;
use App\Form\LoginType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class LoginController extends AbstractController
{
    private UserRepository $userRepository;
    private SerializerInterface $serializer;

    #[Required]
    public function setSerializerInterface(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    #[Required]
    public function setUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/login-view', name: 'login_template')]
    public function loginView(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('UserTemplate/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/', name: 'app')]
    public function admin(AuthenticationUtils $authenticationUtils): Response
    {
        $lastUsername = $authenticationUtils->getLastUsername();

        $this->addFlash('message', 'Bienvenido ' . $lastUsername);
        return $this->render('UserTemplate/dashboard.html.twig', []);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): RedirectResponse
    {
        return $this->redirectToRoute('login_template');
    }
}
