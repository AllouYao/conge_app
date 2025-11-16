<?php

namespace App\Form;

use App\Entity\Service;
use App\Entity\Category;
use App\Entity\Fonction;
use App\Entity\Personal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PersonalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matricule')
            ->add('firstName')
            ->add('lastName')
            ->add('genre',ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin' => 'F',
                ],
                'placeholder' => 'Choisir le genre',
            ])
            ->add('address')
            ->add('telephone')
            ->add('email')
            ->add('categorie', EntityType::class, [
                'class' => Category::class,
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
            ])
            ->add('fonctions', EntityType::class, [
                'class' => Fonction::class,
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personal::class,
        ]);
    }
}
