<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Service\Attribute\Required;

class LoginController extends AbstractController
{
    private AuthorizationCheckerInterface $authorizationChecker;

    #[Required]
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): void
    {
        $this->authorizationChecker = $authorizationChecker;
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

    #[Route('/', name: 'app')]
    public function admin(AuthenticationUtils $authenticationUtils): Response
    {
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('message', 'Bienvenido ' . $lastUsername);

            return $this->render('UserTemplate/dashboard.html.twig', []);
        } elseif ($this->authorizationChecker->isGranted('ROLE_USER')){
            return $this->redirectToRoute('product_list_view_user');
        } else{
            return $this->redirectToRoute('login_template');
        }
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): RedirectResponse
    {
        return $this->redirectToRoute('login_template');
    }
}
