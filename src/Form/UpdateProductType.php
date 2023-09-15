<?php

declare(strict_types=1);

namespace App\Form;

use App\Document\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nombre'])->setRequired(false)
            ->add('price', NumberType::class, ['label' => 'Precio'])->setRequired(false)
            ->add('amount', NumberType::class, [
                'label' => 'Cantidad',
                'constraints' => [
                    new Assert\GreaterThan(0, null, 'El valor debe ser mayor a 0')
                    ]
            ])->setRequired(false)
            ->add('status', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => [
                    'Terminado' => 'expired',
                    'Disponible' => 'available',
                ],
                'attr' => [
                    'class' => 'form-control mb-4'
                ]
            ])->setRequired(false);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => Product::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
