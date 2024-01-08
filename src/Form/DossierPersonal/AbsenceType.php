<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Absence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\CustomType\DateCustomType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Utils\Status;


class AbsenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startedDate', DateCustomType::class, [
            'attr' => [
                'class' => 'form-control form-control-sm'
            ],
            'html5' => true,
            'widget' => 'single_text',
            'required' => true
        ])
            ->add('endedDate', DateCustomType::class, [
            'attr' => [
                'class' => 'form-control form-control-sm'
            ],
            'html5' => true,
            'widget' => 'single_text',
            'required' => true
        ])
            ->add(
                'justified',
                ChoiceType::class,
                [
                    'choices' => [
                        'NON' => false,
                        'OUI' => true,
                    ],
                    'label' => 'Type',
                    'multiple' => false,
                    'expanded' => false,
                    'attr' => [
                        'class' => 'form-select select2',
                    ],
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => $this->getTypeAbsences(),
                    'label' => 'Minute',
                    'multiple' => false,
                    'expanded' => false,
                    'attr' => [
                        'class' => 'form-select select2',
                    ],
                ]
            )
            ->add('description',TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Absence::class,
        ]);
    }
    private function getTypeAbsences(): array
    {
        $typeAbsences = [];
        foreach(Status::TYPE_ABSENCE as $typeAbsence){
            
            $typeAbsences[$typeAbsence] =  $typeAbsence;
            
        }
        return $typeAbsences;
    }
}