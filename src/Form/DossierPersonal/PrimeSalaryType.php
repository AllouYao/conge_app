<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\DetailSalary;
use App\Entity\Settings\Primes;
use App\Utils\Status;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrimeSalaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prime', EntityType::class, [
                'class' => Primes::class,
                'attr' => [
                    'data-plugin' => 'customselect',
                    'class' => 'prime-salary'
                ],
                'placeholder' => 'Sélectionner une prime',
                'choice_attr' => function (Primes $primes) {
                    return [
                        'data-taux' => $primes->getTaux()
                    ];
                },
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('prime')
                        ->where('prime.code in (:code)')
                        ->setParameter('code', [
                            Status::PRIME_OUTILLAGE,
                            Status::PRIME_PANIER,
                            Status::PRIME_SALISSURE,
                            Status::PRIME_TENUE_TRAVAIL
                        ]);
                }
            ])
            ->add('smigHoraire', TextType::class, [
                'attr' => [
                    'readonly' => true
                ],
                'required' => true
            ])
            ->add('taux', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'readonly' => true
                ]
            ])
            ->add('amountPrime', TextType::class, [
                'attr' => [
                    'readonly' => true
                ],
                'required' => true
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DetailSalary::class
        ]);
    }
}