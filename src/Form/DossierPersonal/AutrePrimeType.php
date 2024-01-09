<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\DetailPrimeSalary;
use App\Entity\Settings\Primes;
use App\Utils\Status;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutrePrimeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prime', EntityType::class, [
                'class' => Primes::class,
                'attr' => [
                    'data-plugin' => 'customselect',
                    'class' => 'autre-prime'
                ],
                'placeholder' => 'Sélectionner une prime',
                'choice_attr' => function (Primes $primes) {
                    return [
                        'data-intitule' => $primes->getIntitule()
                    ];
                },
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('prime')
                        ->where('prime.code in (:code)')
                        ->setParameter('code', [
                            Status::PRIME_FONCTION,
                            Status::PRIME_LOGEMENT,
                            Status::INDEMNITE_FONCTION,
                            Status::INDEMNITE_LOGEMENTS
                        ]);
                }
            ])
            ->add('amount', TextType::class, [
                'attr' => [
                    'class' => 'separator text-end'
                ],
                'required' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DetailPrimeSalary::class
        ]);
    }
}