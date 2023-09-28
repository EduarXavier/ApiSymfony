<?php

declare(strict_types=1);

namespace App\Form;

use App\Document\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class,[
                'label' => false,
                'attr' => [
                    'class' => 'd-none',
                ]
            ])
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
