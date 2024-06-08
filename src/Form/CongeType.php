<?php

namespace App\Form;

use DateTime;
use App\Entity\Conge;
use App\Utils\Status;
use App\Entity\Personal;
use Doctrine\ORM\EntityRepository;
use App\Form\CustomType\DateCustomType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class CongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


        $builder
            ->add('dateDepart', DateCustomType::class,)
            ->add('dateRetour', DateCustomType::class)
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'placeholder' => 'Sélectionner un salarié',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
            ])
            ->add('name', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'
                ]
            ])
            ->add('hireDate', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'
                ]
            ])
            ->add('category', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'

                ]
            ])
            ->add('dernierRetour', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'

                ]
            ])
            ->add('dateReprise', DateCustomType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conge::class,
        ]);
    }
}
