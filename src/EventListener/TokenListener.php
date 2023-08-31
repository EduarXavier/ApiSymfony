<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class TokenListener
{
    private JWTEncoderInterface $jwtEncoder;

    public function __construct(JWTEncoderInterface $jwtEncoder)
    {
        $this->jwtEncoder = $jwtEncoder;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->get('_route');

        if
        (
            $routeName !== 'product_list'
            && $routeName !== 'login'
            && $routeName !== "addUser"
            && $routeName !== "logout"
            && $routeName !== "login_template"
            && $routeName !== "product_details"
            && $routeName !== "add_product"
            && $routeName !== "update_product"
            && $routeName !== "delete_product"
            && $routeName !== "invoices_list"
            && $routeName !== "invoices_details"
            && $routeName !== "create_invoice_document"
            && $routeName !== "add_product_shopping_cart"
            && $routeName !== "create_invoice_view"
            && $routeName !== "shopping_cart_list"
            && $routeName !== "pay_invoice_view"
            && $routeName !== "delete_invoice_view"
            && $routeName !== "delete_shopping_cart_view"
            && $routeName !== "delete_product_to_shopping_cart_view"
        )
        {
            try
            {
                $authorizationHeader = $request->headers->get('Authorization');

                if (!$authorizationHeader)
                {
                    throw new AccessDeniedException('Token no proporcionado');
                }

                $tokenParts = explode(' ', $authorizationHeader);

                if (count($tokenParts) !== 2 || $tokenParts[0] !== 'Bearer')
                {
                    throw new AccessDeniedException('Formato de token inválido');
                }

                $token = $tokenParts[1];
                $decodedToken = $this->jwtEncoder->decode($token);
                $email = $decodedToken['email'];

            }
            catch (JWTDecodeFailureException $exception)
            {
                throw new AccessDeniedException('Token inválido', $exception);
            }
        }
    }
}
