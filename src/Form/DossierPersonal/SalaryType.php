<?php

namespace App\Form\DossierPersonal;

use App\Utils\Status;
use App\Entity\Settings\Avantage;
use Symfony\Component\Form\FormEvent;
use App\Entity\DossierPersonal\Salary;
use Symfony\Component\Form\FormEvents;
use App\Entity\DossierPersonal\Personal;
use Symfony\Component\Form\AbstractType;
use App\Repository\Settings\SmigRepository;
use App\Repository\Settings\PrimesRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\NotBlank;

class SalaryType extends AbstractType
{

    public function __construct(
        private readonly SmigRepository   $smigRepository,
        private readonly PrimesRepository $primesRepository
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('avantage', EntityType::class, [
                'class' => Avantage::class,
                'placeholder' => "Sélectionner le nombre de pièce principale",
                'attr' => [
                    'data-plugin' => 'customselect'
                ],
                'required' => false,
                'choice_attr' => function (Avantage $avantage) {
                    return [
                        'data-total-avantage' => $avantage->getTotalAvantage()
                    ];
                }
            ])
            ->add('baseAmount', TextType::class, [
                'attr' => [
                    'readonly' => true,
                    'class' => 'separator text-end'
                ],
                'required' => false,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('sursalaire', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'total-prime separator text-end'
                ]
            ])
            ->add('primeTransport', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'total-prime separator text-end'
                ],
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('amountAventage', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'total-prime separator text-end'
                ],
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('brutAmount', TextType::class, [
                'attr' => [
                    'readonly' => true,
                    'class' => 'total-prime separator text-end'
                ],
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('brutImposable', TextType::class, [
                'attr' => [
                    'readonly' => true,
                    'class' => 'total-prime separator text-end'
                ],
                'constraints' => [
                    new NotBlank()
                ]
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
            ->add('detailPrimeSalaries', CollectionType::class, [
                "entry_type" => AutrePrimeType::class,
                "allow_add" => true,
                "allow_delete" => true,
                "entry_options" => [
                    "label" => false
                ],
                'by_reference' => false,
            ])
            ->add('detailRetenueForfetaires', CollectionType::class, [
                "entry_type" => DetailRetenueForfetaireType::class,
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
            ])
            ->add('transportImposable', HiddenType::class, [
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
                    $data->setSmig($smig?->getAmount());
                }
            );

        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    /** @var Salary $data */
                    $data = $event->getData();
                    $transportNonImposable = $this->primesRepository->findOneBy(
                        ['code' => Status::TRANSPORT_NON_IMPOSABLE]
                    );
                    $data->setTransportImposable($transportNonImposable?->getTaux());
                }
            );

        $builder
            ->addEventListener(FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    /** @var Salary $data */
                    $data = $event->getData();
                    $totalPrime = 0;

                    foreach ($data->getDetailSalaries() as $detailSalary) {
                        $totalPrime += $detailSalary?->getAmountPrime();
                    }
                    $data
                        ->setTotalPrimeJuridique($totalPrime);
                }
            );

        $builder
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    /** @var Salary $data */
                    $data = $event->getData();
                    $totalAutrePrime = 0;
                    foreach ($data->getDetailPrimeSalaries() as $detailPrimeSalary) {
                        $totalAutrePrime += $detailPrimeSalary?->getAmount();
                    }
                    $data->setTotalAutrePrimes($totalAutrePrime);
                }
            );

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var Salary $data */
                $data = $event->getData();
                $transport = $data->getId() ? $data->getPrimeTransport() : 30000;
                $form = $event->getForm();
                $form->get('primeTransport')->setData($transport);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Salary::class
        ]);
    }
}