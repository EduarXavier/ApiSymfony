<?php

declare(strict_types=1);

namespace App\Services;

use App\Message\NotificationMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Service\Attribute\Required;

#[AsMessageHandler(fromTransport: 'async')]
class EmailService
{
    private MailerInterface $mailer;

    #[Required]
    public function setMailer(MailerInterface $mailer): void
    {
        $this->mailer = $mailer;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(NotificationMessage $message): void
    {
        if ($message->getType() == 'registro') {
            $subject = 'Gracias por registrarse';
            $text = 'Estamos felices por tu registro en nuestra plataforma, muchas gracias';
            $html = 'EmailTemplates/registry.html.twig';
        } else {
            $subject = 'Gracias por su primera compra';
            $text = 'Estamos felices de que empieces tus compras con nosotros';
            $html = 'EmailTemplates/firstShop.html.twig';
        }

        $email = (new TemplatedEmail())
            ->from(new Address('Admin@api.com.co'))
            ->to($message->getUser()->getEmail())
            ->subject($subject)
            ->text($text)
            ->htmlTemplate($html)
            ->context(['user' => $message->getUser()])
        ;

        $this->mailer->send($email);
    }
}
