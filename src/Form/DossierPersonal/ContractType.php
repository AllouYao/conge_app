<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Contract;
use App\Form\CustomType\DateCustomType;
use App\Utils\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeContrat', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'CDD' => Status::CDD,
                    'CDI' => Status::CDI,
                    'CDDI' => Status::CDDI,
                ],
                'placeholder' => 'Sélectionner votre type de contrat',
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('dateEmbauche', DateCustomType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('dateEffet', DateCustomType::class, [
                'required' => false
            ])
            ->add('dateFin', DateCustomType::class, [
                'required' => false
            ])
            ->add('tempsContractuel', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Temps plein' => Status::TEMPS_PLEIN,
                    'Temps partiel' => Status::TEMPS_PARTIEL,
                ],
                'placeholder' => 'Sélectionner votre temps contractuel',
                'required' => false,
            ])
            ->add('refContract');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class
        ]);
    }
}