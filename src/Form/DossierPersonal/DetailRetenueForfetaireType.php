<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\DetailRetenueForfetaire;
use App\Entity\DossierPersonal\RetenueForfetaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DetailRetenueForfetaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('retenuForfetaire', EntityType::class, [
                'class' => RetenueForfetaire::class,
                'attr' => [
                    'data-plugin' => 'customselect',
                    'class' => 'retenue-salary'
                ],
                'placeholder' => 'Sélectionner une retenue forfetaire',
                'choice_attr' => function (RetenueForfetaire $retenueForfetaire) {
                    return [
                        'data-value' => $retenueForfetaire->getValue(),
                        'data-name' => $retenueForfetaire->getCode()
                    ];
                },
                'required' => true,
            ])
            ->add('amount', TextType::class, [
                'attr' => [
                    'readonly' => false
                ],
                'required' => true
            ])
            ->add('amountEmp', TextType::class, [
                'attr' => [
                    'readonly' => false
                ],
                'required' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DetailRetenueForfetaire::class
        ]);
    }
}