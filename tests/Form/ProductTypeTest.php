<?php

namespace App\Tests\Form;

use App\Document\Product;
use App\Form\ProductType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class ProductTypeTest extends KernelTestCase
{
    private Product $product;
    private FormFactoryInterface $formFactory;
    private array $formData;

    public function testSubmitValidData(): void
    {
        $form = $this->formFactory->create(ProductType::class, $this->product);
        $form->submit($this->formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertEquals($this->product->getName(), $this->formData['name']);
        self::assertEquals($this->product->getPrice(), $this->formData['price']);
        self::assertEquals($this->product->getAmount(), $this->formData['amount']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->product = new Product();
        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->formData = [
            'name' => 'Jabon',
            'price' => 1000,
            'amount' => 15
        ];
    }

    protected function tearDown(): void
    {
        unset(
            $this->formData,
            $this->product,
            $this->formFactory
        );
    }
}
