<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Document\Invoice;
use App\Form\InvoiceType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class InvoiceTypeTest extends KernelTestCase
{
    private array $formData;
    private Invoice $invoice;
    private FormFactoryInterface $formFactory;

    public function testSubmitValidData(): void
    {
        $form = $this->formFactory->create(InvoiceType::class, $this->invoice);
        $form->submit($this->formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertEquals($this->invoice->getCode(), $this->formData['code']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->invoice = new Invoice();
        $this->formData = [
            'code' => '650478611714d-1094045112'
        ];
        $this->formFactory = self::getContainer()->get('form.factory');
    }

    protected function tearDown(): void
    {
        unset(
            $this->formData,
            $this->invoice,
            $this->formFactory
        );
    }
}
