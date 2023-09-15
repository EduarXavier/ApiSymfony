<?php

namespace App\Tests\Form;

use App\Document\ProductInvoice;
use App\Form\ProductShoppingCartType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class ProductShoppingCartTypeTest extends KernelTestCase
{
    private array $formData;
    private ProductInvoice $productInvoice;
    private FormFactoryInterface $formFactory;

    public function testSubmitValidData(): void
    {
        $form = $this->formFactory->create(ProductShoppingCartType::class, $this->productInvoice);
        $form->submit($this->formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertEquals($this->productInvoice->getCode(), $this->formData['code']);
        self::assertEquals($this->productInvoice->getAmount(), $this->formData['amount']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->productInvoice = new ProductInvoice();
        $this->formData = [
            'code' => '650478611714d-1094045112',
            'amount' => 10
        ];
        $this->formFactory = self::getContainer()->get('form.factory');
    }

    protected function tearDown(): void
    {
        unset(
            $this->formData,
            $this->productInvoice,
            $this->formFactory
        );
    }
}