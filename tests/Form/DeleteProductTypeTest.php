<?php

namespace App\Tests\Form;

use App\Document\Product;
use App\Form\DeleteProductType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class DeleteProductTypeTest extends KernelTestCase
{
    private array $formData;
    private Product $product;
    private FormFactoryInterface $formFactory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->product = new Product();
        $this->formData = [
            'code' => '650478611714d-Jabon'
        ];
        $this->formFactory = self::getContainer()->get('form.factory');
    }

    protected function tearDown(): void
    {
        unset($this->formData);
        unset($this->product);
        unset($this->formFactory);
    }

    public function testSubmitValidData(): void
    {
        $form = $this->formFactory->create(DeleteProductType::class, $this->product);
        $form->submit($this->formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertEquals($this->product->getCode(), $this->formData['code']);
    }
}