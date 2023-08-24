<?php

namespace App\Form;

use App\Document\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address',TextType::class)->setRequired(false)
            ->add('phone',TextType::class)->setRequired(false)
            ->add('password', TextType::class)->setRequired(false)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => Users::class,
            'validation_groups' => ['update']
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
