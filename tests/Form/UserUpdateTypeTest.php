<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Document\User;
use App\Form\UserType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class UserUpdateTypeTest extends KernelTestCase
{
    private User $user;
    private FormFactoryInterface $formFactory;
    private array $formData;

    public function testSubmitValidData(): void
    {
        $form = $this->formFactory->create(UserType::class, $this->user);
        $form->submit($this->formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertEquals($this->user->getAddress(), $this->formData['address']);
        self::assertEquals($this->user->getPhone(), $this->formData['phone']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->user = new User();
        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->formData = [
            'address' => 'calle falsa',
            'phone' => '30000000'
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