<?php

namespace App\Form\ImportFile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ImportFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fileName',FileType::class,[
                'mapped' => false,
                /* 'constraints'=>[
                    new Assert\File([
                        'maxSize' => '256M',
                        'extensions' => [
                            'xlsx',
                        ],
                        'extensionsMessage' => 'Veillez import le fichier excel valide!',
                    ])
                ], */
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}

