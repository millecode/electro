<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Servicess;
use App\Entity\Reparation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ReparationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => "L'email du client",
                'mapped' => false,
                'attr' => [
                    'class' => 'border border-dark'
                ],
                'required' => false
            ])

            ->add('phone', TelType::class, [
                'label' => 'Numeros de Télèphone :',
                'attr' => [
                    'class' => 'border border-dark'
                ],
                'required' => false
            ])

            ->add('service', EntityType::class, [
                'label' => 'Choisisez un service',
                'class' => Servicess::class,
                'query_builder' => function (\App\Repository\ServicessRepository $repo) {
                    return $repo->createQueryBuilder('s')
                        ->where('s.status = :status')
                        ->andWhere('s.service_supp = :stat')
                        ->setParameter('status', 1) // Filtrer uniquement les services avec le statut 1
                        ->setParameter('stat', 0); // Filtrer uniquement les services avec le statut 1
                },
                'choice_label' => function ($service) {
                    // Afficher le titre et le prix côte à côte
                    return $service->getTitre() . ' - ' . $service->getPrix() . ' FDJ';
                },
                'attr' => [
                    'class' => 'form-control border border-dark'
                ]
            ])

            ->add('imageFile', VichImageType::class, [
                'label' => "Image du produit :",
                "required" => false,
                'allow_delete' => true, // Ajoute une option pour supprimer l'image
                'download_label' => 'Voir l\'image actuelle', // Ajoute un lien pour voir l'image
                'download_uri' => true, // Active l'URL de téléchargement
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => "Description du produit :",
                'attr' => [
                    'rows' => 5, // Hauteur en nombre de lignes
                    'cols' => 50, // Longueur en nombre de colonnes
                    'style' => 'resize: none;', // Désactive le redimensionnement manuel si nécessaire
                    'class' => 'border border-dark'
                ],

            ])
            ->add('prix', MoneyType::class, [
                'currency' => 'FDJ', // Cette option peut être remplacée ici
                'scale' => 0,        // Par exemple, pour ne pas afficher de décimales
                'label' => "Le prix de la reparation",
                'attr' => [
                    'class' => 'border border-dark'
                ],
                'required' => false
            ])
            ->add('date_repri', DateTimeType::class, [
                'label' => 'Date de repise',
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])




        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reparation::class,
        ]);
    }
}
