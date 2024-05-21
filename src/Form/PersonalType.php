<?php

namespace App\Form;

use App\Entity\Personal;
use App\Form\CustomType\DateCustomType;
use App\Utils\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PersonalType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matricule', TextType::class, [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('genre', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Masculin' => Status::MASCULIN,
                    'Féminin' => Status::FEMININ
                ],
                'placeholder' => '--- Sélectionner un genre ---',
                'required' => false,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('birthday', DateCustomType::class, [
                'required' => false
            ])
            ->add('lieuNaissance', TextType::class, [
                'required' => false
            ])
          
            ->add('address', TextType::class, [
                'required' => false
            ])
            ->add('telephone', TextType::class, [
                'required' => false
            ])
            ->add('email', TextType::class, [
                'required' => false
            ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personal::class,
        ]);
    }
}
