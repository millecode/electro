<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Votre nom complet :',
                'attr' => [
                    'class' => 'border border-primary'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Entrer un Email :',
                'attr' => [
                    'class' => 'border border-primary'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Votre mot de passe :',
                'attr' => [
                    'class' => 'border border-primary'
                ]
            ])

            ->add('phone', TelType::class, [
                'label' => 'Numeros de Télèphone :',
                'attr' => [
                    'class' => 'border border-primary'
                ]
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Votre Adresse :',
                'attr' => [
                    'class' => 'border border-primary'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
