<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProfileShowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            //->add('firstName')
            //->add('lastName')
            //->add('fonction')
            //->add('telephone')
            ->add('username',TextType::class,[
                'disabled'=>true,
            ])
            ->add('email',TextType::class,[
                'disabled'=>true,
            ])
            ->add('customRoles', EntityType::class, [
                'class' => Role::class,
                'placeholder' => 'Choisir un role',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'multiple'=>true,
                'disabled'=>true,
             ]);
          
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

