<?php

namespace App\Form;

use App\Entity\Contacts;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => "Votre Nom",
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => "Votre email",
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])
            ->add('phone', IntegerType::class, [
                'label' => "Numero de télèphone",
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])
            ->add('sujet', TextType::class, [
                'label' => "Sujet",
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => "Décrire votre besoins",
                'attr' => [
                    'rows' => 5, // Hauteur en nombre de lignes
                    'cols' => 50, // Longueur en nombre de colonnes
                    'style' => 'resize: none;', // Désactive le redimensionnement manuel si nécessaire
                    'class' => 'border border-dark'
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contacts::class,
        ]);
    }
}
