<?php


namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\HeureSup;
use App\Form\CustomType\DateCustomType;
use App\Utils\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HeureSupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startedDate', DateCustomType::class)
            ->add('endedDate', DateCustomType::class)
            ->add('startedHour', TimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('endedHour', TimeType::class, [
                'widget' => 'single_text',
            ])
            ->add(
                'typeDay',
                ChoiceType::class,
                [
                    'choices' => [
                        'NORMAL' => Status::NORMAL,
                        'DIMANCHE/FÉRIÉ' => Status::DIMANCHE_FERIE,
                    ],
                    'label' => 'Type',
                    'multiple' => false,
                    'expanded' => false,
                    'attr' => [
                        'class' => 'form-select select2',
                    ],
                    'data' => 'DEBUT'
                ]
            )
            ->add(
                'typeJourOrNuit',
                ChoiceType::class,
                [
                    'choices' => [
                        'JOUR' => Status::JOUR,
                        'NUIT' => Status::NUIT,
                    ],
                    'label' => 'Type',
                    'multiple' => false,
                    'expanded' => false,
                    'attr' => [
                        'class' => 'form-select select2',
                    ],
                    'data' => 'DEBUT'
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HeureSup::class,
        ]);
    }
}