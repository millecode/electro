<?php

namespace App\Form;

use App\Entity\Servicess;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServicessType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du service',
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => "Image du service :",
                "required" => false,
                'allow_delete' => true, // Ajoute une option pour supprimer l'image
                'download_label' => 'Voir l\'image actuelle', // Ajoute un lien pour voir l'image
                'download_uri' => true, // Active l'URL de téléchargement
                'attr' => [
                    'class' => 'border border-dark'
                ]
            ])
            ->add('prix', MoneyType::class, [
                'scale' => 0,        // Par exemple, pour ne pas afficher de décimales
                'label' => 'Prix du service',
                'attr' => [
                    'class' => 'border border-dark'
                ],
                'currency' => 'DJF', // Cette option peut être remplacée ici
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Servicess::class,
        ]);
    }
}
