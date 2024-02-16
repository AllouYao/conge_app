<?php

namespace App\Form\Auth;

use App\Entity\User;
use App\Entity\Auth\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class AuthUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            //->add('firstName')
            //->add('lastName')
            //->add('fonction')
            //->add('telephone')
            ->add('username')
            ->add('email')
            ->add('customRoles', EntityType::class, [
                'class' => Role::class,
                'placeholder' => 'Choisir un role',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'multiple'=>true,
            ]);
          
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

