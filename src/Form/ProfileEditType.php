<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('email')
            ->add('newPassword',PasswordType::class,[
                'mapped'=>false
            ])
            ->add('holdPassword',PasswordType::class,[
                'mapped'=>false,
                'constraints' => [
                    new UserPassword()
                ]
            ])
            ->add('confirmePassword',PasswordType::class,[
                'mapped'=>false
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

