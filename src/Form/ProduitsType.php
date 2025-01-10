<?php

namespace App\Form;

use App\Entity\Produits;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProduitsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du produit :',
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
                    'class' => 'form-control border border-dark'
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
                'label' => 'Prix unitaire du produit :',
                'attr' => [
                    'class' => 'form-control border border-dark'
                ]
            ])
            ->add('quantiter', IntegerType::class, [
                'label' => 'Quantiter disponible',
                'attr' => [
                    'class' => 'form-control border border-dark'
                ]
            ])
            ->add('categorie', EntityType::class, [
                'label' => 'Choisisez une Categorie :',
                'class' => Categorie::class,
                'choice_label' => 'categorie',
                'attr' => [
                    'class' => 'form-control border border-dark'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produits::class,
        ]);
    }
}
