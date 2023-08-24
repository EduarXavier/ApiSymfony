<?php

namespace App\EventListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TokenListener
{
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->get('_route');

        if ($routeName !== 'product_list' && $routeName !== 'login') {
            try
            {
                $token = $request->headers->get('Authorization');

                if (!$token) {
                    throw new AccessDeniedException('Token no proporcionado');
                }

                $decodedToken = $this->jwtManager->decode($token);

                // Aquí puedes acceder a la información del token decodificado si es necesario
                // $username = $decodedToken['username'];

            }
            catch (JWTDecodeFailureException $exception)
            {
                throw new AccessDeniedException('Token inválido', $exception);
            }
        }
    }
}