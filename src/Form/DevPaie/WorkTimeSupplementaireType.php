<?php

namespace App\Form\DevPaie;

use App\Entity\DevPaie\WorkTime;
use App\Utils\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class WorkTimeSupplementaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Majoration à 15%' => Status::MAJORATION_15_PERCENT,
                    'Majoration à 50%' => Status::MAJORATION_50_PERCENT,
                    'Majoration à 75%' => Status::MAJORATION_75_PERCENT,
                    'Majoration à 100%' => Status::MAJORATION_100_PERCENT,
                ],
                'multiple' => false,
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'placeholder' => 'Choisir une majoration ',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('hourValue')
            ->add('rateValue', NumberType::class)
            ->add('code', HiddenType::class, [
                'data' => Status::SUPPLEMENTAIRE
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkTime::class,
        ]);
    }
}
