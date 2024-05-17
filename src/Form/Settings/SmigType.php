<?php

namespace App\Form\Settings;

use App\Entity\Settings\CategorySalarie;
use App\Entity\Settings\Smig;
use App\Form\CustomType\DateCustomType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SmigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            //->add('dateDebut', DateCustomType::class)
           // ->add('dateFin', DateCustomType::class)
            ->add('categorySalaries', EntityType::class, [
                'class' => CategorySalarie::class,
                'placeholder' => "Sélectionner une categorie de salarié",
                'attr' => [
                    'class' => 'form-select wide',
                    'data-plugin' => "customselect"
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
                'multiple' => true,
                'expanded' => false
            ])
            ->add('amount', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'separator text-end'
                ]
            ])
            ->add('isActive', ChoiceType::class, [
                'choices' => [
                    'Oui' => true,
                    'Nom' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => [
                    'class' => 'radio-inline'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Smig::class,
        ]);
    }
}
