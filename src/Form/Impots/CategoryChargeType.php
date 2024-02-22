<?php

namespace App\Form\Impots;

use App\Entity\Impots\CategoryCharge;
use App\Utils\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryChargeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeCharge', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Charges fiscales' => Status::FISCALE_CHARGE,
                    'Charges sociales' => Status::SOCIALE_CHARGE,
                ],
                'placeholder' => 'Sélectionner le type de charge',
                'required' => true
            ])
            ->add('category', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Charge salariale' => Status::PERSONAL_CHARGE,
                    'Charge patronnale' => Status::EMPLOYER_CHARGE,
                ],
                'placeholder' => 'Sélectionner la catégorie de charge',
                'required' => true
            ])
            ->add('codification')
            ->add('intitule')
            ->add('description')
            ->add('value');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategoryCharge::class,
        ]);
    }
}
