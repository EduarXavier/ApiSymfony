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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, JWTEncoderInterface $encoder): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(LoginType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userFind = $this->userRepository->findByEmail($user->getEmail(), $this->documentManager);

            if ($userFind && password_verify($user->getPassword(), $userFind->getPassword())) {
                $token = $encoder->encode(['email' => $user->getEmail()]);

                return $this->json(['token' => $token]);
            }

            return $this->json(['Error' => 'Credenciales Inválidas'], 400);
        }

        return $this->json(['Error' => 'Datos de formulario inválidos'], 400);
    }

    #[Route('/login-view', name: 'login_template')]
    public function loginView(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(LoginType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userFind = $this->userRepository->findByEmail($user->getEmail(), $this->documentManager);

            if ($userFind && password_verify($user->getPassword(), $userFind->getPassword())) {

                $session = $request->getSession();
                $session->set('user', $user->getEmail());
                $session->set('rol', $userFind->getRol());
                $session->set('document', $userFind->getDocument());
                $session->set('shopping-cart', array());

                $this->addFlash('message', 'Bienvenido ' . $session->get('user'));
                return $this->render('UserTemplate/dashboard.html.twig', []);
            }
            else {
                $this->addFlash('message', 'Credenciales invalidas');
                $this->redirectToRoute('login_template');
            }
        }

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
