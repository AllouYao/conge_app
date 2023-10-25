<?php

namespace App\Form\Settings;

use App\Entity\Settings\Category;
use App\Entity\Settings\CategorySalarie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categorySalarie', EntityType::class, [
                'class' => CategorySalarie::class,
                'attr' => [
                    'data-plugin' => 'customselect',
                    'class' => 'form-select form-select-sm'
                ],
                'placeholder' => 'Sélectionner une catégorie de salaire'
            ])
            ->add('intitule', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('amount', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual([
                        'value' => 75000,
                        'message' => 'Le montant doit être supérieur ou égal à 75000.'
                    ])
                ],
                'attr' => [
                    'class' => 'separator'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
