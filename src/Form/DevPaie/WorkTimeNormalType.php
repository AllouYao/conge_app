<?php

namespace App\Form\DevPaie;

use App\Entity\DevPaie\WorkTime;
use App\Utils\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkTimeNormalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type',TextType::class,[
                'disabled'=>true,
                'data'=>'NORMAL'
            ])
            ->add('code',HiddenType::class,[
                'data'=>'NORMAL'
            ])
            ->add('hourValue')
            ->add('rateValue',NumberType::class,[
                'data'=>Status::TAUX_HEURE
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
