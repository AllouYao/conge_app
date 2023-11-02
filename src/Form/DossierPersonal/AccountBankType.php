<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\AccountBank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountBankType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bankId')
            ->add('code')
            ->add('numCompte')
            ->add('rib')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountBank::class,
        ]);
    }
}
