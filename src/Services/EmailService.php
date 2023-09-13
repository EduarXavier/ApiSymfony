<?php

declare(strict_types=1);

namespace App\Services;

use App\Document\User;
use App\Document\UserInvoice;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmail(User|UserInvoice $user, string $mode): void
    {
        if ($mode == 'registro') {
            $subject = 'Gracias por registrarse';
            $text = 'Estamos felices por tu registro en nuestra plataforma, muchas gracias';
            $html = 'EmailTemplates/registry.html.twig';
        } else {
            $subject = 'Gracias por su primera compra';
            $text = 'Estamos felices de que empieces tus compras con nosotros';
            $html = "EmailTemplates/firstShop.html.twig";
        }

        $user->setName($user->getName());
        $email = (new TemplatedEmail())
            ->from(new Address('est_ex_avendano@fesc.edu.co'))
            ->to($user->getEmail())
            ->subject($subject)
            ->text($text)
            ->htmlTemplate($html)
            ->context(["user" => $user])
            ;

        $this->mailer->send($email);
    }
}