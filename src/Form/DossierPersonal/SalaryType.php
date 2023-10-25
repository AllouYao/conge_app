<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Salary;
use App\Repository\Settings\SmigRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalaryType extends AbstractType
{
    public function __construct(private readonly SmigRepository $smigRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('baseAmount', TextType::class, [
                'attr' => [
                    'readonly' => true,
                    'class' => 'separator'
                ],
                'required' => false
            ])
            ->add('sursalaire', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'total-prime separator'
                ]
            ])
            ->add('primeTransport', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'total-prime separator'
                ]
            ])
            ->add('brutAmount', TextType::class, [
                'attr' => [
                    'readonly' => true,
                    'class' => 'total-prime separator'
                ],
            ])
            ->add('brutImposable', TextType::class, [
                'attr' => [
                    'readonly' => true,
                    'class' => 'total-prime separator'
                ],
            ])
            ->add('detailSalaries', CollectionType::class, [
                "entry_type" => PrimeSalaryType::class,
                "allow_add" => true,
                "allow_delete" => true,
                "entry_options" => [
                    "label" => false
                ],
                'by_reference' => false,
            ])
            ->add('smig', HiddenType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'separator'
                ]
            ]);

        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    /** @var Salary $data */
                    $data = $event->getData();
                    $smig = $this->smigRepository->active();
                    $data->setSmig($smig?->getAmount() ?? 0);
                }
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Salary::class
        ]);
    }
}