<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\ChargePeople;
use App\Form\CustomType\DateCustomType;
use App\Utils\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChargePeopleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastName')
            ->add('firstName')
            ->add('birthday', DateCustomType::class)
            ->add('gender', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect'
                ],
                'choices' => [
                    'Masculin' => Status::MASCULIN,
                    'Féminin' => Status::FEMININ
                ],
                'placeholder' => 'Sélectionner un genre'
            ])
            ->add('numPiece')
            ->add('contact', TextType::class, [
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

            'data_class' => ChargePeople::class,
        ]);
    }
}