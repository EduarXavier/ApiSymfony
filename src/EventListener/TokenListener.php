<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class TokenListener
{
    private JWTEncoderInterface $jwtEncoder;
    private UserRepository $userRepository;

    public function __construct(JWTEncoderInterface $jwtEncoder, UserRepository $userRepository)
    {
        $this->jwtEncoder = $jwtEncoder;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws JWTDecodeFailureException
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $request = $event->getRequest();
        $pattern = '/api/';
        $pathInfo = $request->getPathInfo();

        if (preg_match($pattern, $pathInfo)) {
            $authorizationHeader = $request->headers->get('Authorization');

            if (!$authorizationHeader) {
                throw new AccessDeniedException('Token no proporcionado');
            }

            $tokenParts = explode(' ', $authorizationHeader);

            if (count($tokenParts) !== 2 || $tokenParts[0] !== 'Bearer') {
                throw new AccessDeniedException('Formato de token inválido');
            }

            $token = $tokenParts[1];
            $decodedToken = $this->jwtEncoder->decode($token);
            $email = $decodedToken['username'];
            if (!$this->userRepository->findBy(["email" => $email])) {
                throw new \Exception('El usuario proporcionado no se ha encontrado');
            }
        }
    }
}
