<?php

namespace App\Form;

use App\Entity\MethodePaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MethodePaimentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'label' => 'Ecrire le nom du type de paiement',
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone asscoiée au Mode de paiement',
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MethodePaiement::class,
        ]);
    }
}
