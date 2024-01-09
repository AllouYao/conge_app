<?php

namespace App\Form\Settings;

use App\Entity\Settings\Primes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrimesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('intitule')
            ->add('code')
            ->add('taux', TextType::class, [
                'attr' => [
                    'class' => 'separator text-end'
                ],
                'required' => false
            ])
            ->add('description');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Primes::class,
        ]);
    }
}
