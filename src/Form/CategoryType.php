<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Personal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('libelle')
        ->add('code');

        /* 
        ->add('personals', EntityType::class, [
            'class' => Personal::class,
            'attr' => [
                'data-plugin' => 'customselect',
            ],
            'multiple' => true
        ])
        */
    }

public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => Category::class,
    ]);
}
}
