<?php

namespace App\Form;

use App\Entity\Logos;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', VichImageType::class, [
                'label' => "Logo :",
                "required" => true,
                'allow_delete' => true, // Ajoute une option pour supprimer l'image
                'download_label' => 'Voir l\'image actuelle', // Ajoute un lien pour voir l'image
                'download_uri' => true, // Active l'URL de téléchargement
                'attr' => [
                    'class' => 'form-control border border-dark'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logos::class,
        ]);
    }
}
