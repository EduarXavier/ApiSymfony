<?php

declare(strict_types=1);

namespace App\Form;

use App\Document\ProductInvoice;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductShoppingCartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'd-none'
                ]
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Cantidad',
                'constraints' => [
                    new Assert\GreaterThan(0, null, 'El valor debe ser mayor a 0')
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => ProductInvoice::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
