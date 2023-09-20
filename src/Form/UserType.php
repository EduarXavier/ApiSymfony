<?php

declare(strict_types=1);

namespace App\Form;

use App\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class,  [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('document', TextType::class,  [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('rol', TextType::class,  [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('address', TextType::class,  [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('phone', TextType::class,  [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('email', EmailType::class,  [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('rol', TextType::class,  [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => User::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
