<?php

namespace App\Form\DevPaie;

use App\Utils\Status;
use App\Entity\DevPaie\WorkTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class WorkTimeSupplementaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type',ChoiceType::class, [
                'choices' => [
                    'Majoration à 15%' => 'MAJORATION_15_PERCENT',
                    'Majoration à 50%' => 'MAJORATION_50_PERCENT',
                    'Majoration à 75%' => 'MAJORATION_75_PERCENT',
                    'Majoration à 100%' => 'MAJORATION_100_PERCENT',
                ],
                'multiple' => false,
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'placeholder' => 'Choisir une majoration '
            ])
            ->add('hourValue')
            ->add('rateValue',NumberType::class,[
            ])
            ->add('code',HiddenType::class,[
                'data'=>'SUPPLEMENTAIRE'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkTime::class,
        ]);
    }
}
