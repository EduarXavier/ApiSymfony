<?php

declare(strict_types=1);

namespace App\Form;

use App\Document\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PayInvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => Invoice::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
