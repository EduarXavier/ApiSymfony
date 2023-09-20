<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Document\Invoice;
use App\Document\User;
use App\Form\ShoppingCartType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class ShoppingCartTypeTest extends KernelTestCase
{
    private array $formData;
    private Invoice $invoice;
    private FormFactoryInterface $formFactory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = self::getContainer()->get('form.factory');
        $this->invoice = new Invoice();
        $this->formData = [
            'products' => [
                [
                    "code" => "65035c6f3b747-Eduar",
                    "amount" => 2
                ]
            ],
            'user' => [
                'document' => '100'
            ]
        ];
    }

    protected function tearDown(): void
    {
        unset($this->formData);
        unset($this->invoice);
        unset($this->formFactory);
    }

    public function testSubmitValidData(): void
    {
        $form = $this->formFactory->create(ShoppingCartType::class, $this->invoice);
        $form->submit($this->formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertIsArray($this->invoice->getProducts()->toArray());
        self::assertInstanceOf(User::class, $this->invoice->getUser());
    }
}