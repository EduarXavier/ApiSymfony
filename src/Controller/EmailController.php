<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class EmailController extends AbstractController
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/send-email/{user}', name: 'send_email')]
    public function sendEmail(string $user, string $mode)
    {
        if ($mode == 'registro') {
            $subject = 'Gracias por registrarse';
            $text = 'Estamos felices por tu registro en nuestra plataforma, muchas gracias';
            $html = '
                <div style="font-family: Arial, sans-serif; background-color: #f0f0f0; justify-content: center; align-items: center; width: 100%; margin: 0;">
                  <div style="background-color: #ffffff; border-radius: 10px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px; text-align: center;">
                    <h1 style="color: #333333; margin-top: 0;">Bienvenido/a</h1>
                    <p style="color: #666666;">¡Nos alegra tenerte aquí! Esperamos que disfrutes de tu tiempo con nosotros.</p>
                    <img src="https://t4.ftcdn.net/jpg/04/46/40/87/360_F_446408796_sO3c3ZIuWMgvXNbfXM4Hyqt7pLtGzKQo.jpg"/>
                  </div>
                </div>';
        } else {
            $subject = 'Gracias por su primera compra';
            $text = 'Estamos felices de que empieces tus compras con nosotros';
            $html = '
                <div style="font-family: Arial, sans-serif; background-color: #f0f0f0; justify-content: center; align-items: center; width: 100%; margin: 0;">
                  <div style="background-color: #ffffff; border-radius: 10px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px; text-align: center;">
                    <h1 style="color: #333333; margin-top: 0;">Gracias por tu compra</h1>
                    <p style="color: #666666;">Hemos visto que acabas de hacer tu primera compra con nosotros. ¡Qué felicidad!</p>
                    <img src="https://s3.amazonaws.com/cdn.freshdesk.com/data/helpdesk/attachments/production/2100545284/original/wEoHNm3YHs8UnDWRm95e22HNz_gxmSP1jA.png?1486368694"/>
                  </div>
                </div>';
        }

        $email = (new Email())
            ->from('est_ex_avendano@fesc.edu.co')
            ->to($user)
            ->subject($subject)
            ->text($text)
            ->html($html);

        $this->mailer->send($email);
    }
}
