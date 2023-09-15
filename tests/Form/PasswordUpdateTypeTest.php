<?php

namespace App\Tests\Form;

use App\Document\User;
use App\Form\PasswordUpdateType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class PasswordUpdateTypeTest extends KernelTestCase
{
    private array $formData;
    private User $user;
    private FormFactoryInterface $formFactory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->user = new User();
        $this->formData = [
            'password' => '123123123'
        ];
        $this->formFactory = self::getContainer()->get('form.factory');
    }

    protected function tearDown(): void
    {
        unset($this->formData);
        unset($this->invoice);
        unset($this->formFactory);
    }

    public function testSubmitValidData(): void
    {
        $form = $this->formFactory->create(PasswordUpdateType::class, $this->user);
        $form->submit($this->formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertEquals($this->user->getPassword(), $this->formData['password']);
    }
}