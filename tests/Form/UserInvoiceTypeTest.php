<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Document\User;
use App\Form\UserInvoiceType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class UserInvoiceTypeTest extends KernelTestCase
{
    private User $user;
    private FormFactoryInterface $formFactory;
    private array $formData;

    public function testSubmitValidData(): void
    {
        $form = $this->formFactory->create(UserInvoiceType::class, $this->user);
        $form->submit($this->formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertEquals($this->user->getDocument(), $this->formData['document']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->user = new User();
        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->formData = [
            'document' => '100'
        ];
    }

    protected function tearDown(): void
    {
        unset(
            $this->formData,
            $this->user,
            $this->formFactory
        );
    }
}