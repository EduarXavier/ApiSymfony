<?php

namespace App\Tests\Services;

use App\Document\User;
use App\Services\EmailService;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class EmailServiceTest extends KernelTestCase
{
    private EmailService $emailService;
    private MailerInterface $mailer;

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendEmailRegistro(): void
    {
        $user = new User();
        $user->setName('persona falsa');
        $user->setEmail('persona@falsa.com');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($user) {
                return $email->getTo()[0]->getAddress() === $user->getEmail()
                    && $email->getSubject() === 'Gracias por registrarse'
                    && $email->getTextBody() === 'Estamos felices por tu registro en nuestra plataforma, muchas gracias'
                    && $email->getHtmlTemplate() === 'EmailTemplates/registry.html.twig'
                    && $email->getContext() === ['user' => $user];
            }));

        $this->emailService->sendEmail($user, 'registro');
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendEmailFirstShop(): void
    {
        $user = new User();
        $user->setName('persona falsa');
        $user->setEmail('persona@falsa.com');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($user) {
                return $email->getTo()[0]->getAddress() === $user->getEmail()
                    && $email->getSubject() === 'Gracias por su primera compra'
                    && $email->getTextBody() === 'Estamos felices de que empieces tus compras con nosotros'
                    && $email->getHtmlTemplate() === 'EmailTemplates/firstShop.html.twig'
                    && $email->getContext() === ['user' => $user];
            }));

        $this->emailService->sendEmail($user, 'firstShop');
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->emailService = new EmailService();
        $this->emailService->setMailer($this->mailer);
    }

    protected function tearDown(): void
    {
        unset(
            $this->emailService,
            $this->mailer
        );
    }
}
