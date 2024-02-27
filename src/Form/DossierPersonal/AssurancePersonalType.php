<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\ChargePeople;
use App\Entity\DossierPersonal\DetailRetenueForfetaire;
use App\Entity\DossierPersonal\Personal;
use App\Entity\DossierPersonal\RetenueForfetaire;
use App\Repository\DossierPersonal\ChargePeopleRepository;
use App\Utils\Status;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssurancePersonalType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount')
            ->add('retenuForfetaire', EntityType::class, [
                'class' => RetenueForfetaire::class,
                'attr' => [
                    'data-plugin' => 'customselect',
                    'class' => 'retenue-salary'
                ],
                'placeholder' => 'Sélectionner une retenue forfetaire',
                'choice_attr' => function (RetenueForfetaire $retenueForfetaire) {
                    return [
                        'data-value' => $retenueForfetaire->getValue(),
                        'data-code' => $retenueForfetaire->getCode()
                    ];
                },
                'required' => true,
            ])
            ->add('Personal', EntityType::class, [
                'class' => Personal::class,
                'choice_label' => 'matricule',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->join('p.contract', 'contract')
                        ->leftJoin('p.departures', 'departures')
                        ->where('p.modePaiement in (:modePaiement)')
                        ->andWhere('departures.id IS NULL')
                        ->setParameter('modePaiement', [Status::VIREMENT, Status::CHEQUE]);
                },
                'placeholder' => 'Sélectionner un matricule',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choice_attr' => function (Personal $personal) {
                    return [
                        'data-name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                        'data-hireDate' => $personal->getContract()?->getDateEmbauche()->format('d/m/Y'),
                        'data-category' => '( ' . $personal->getCategorie()->getCategorySalarie()->getName() . ' ) - ' . $personal->getCategorie()->getIntitule()
                    ];
                }
            ])
            ->add('name', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'
                ]
            ])
            ->add('hireDate', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'
                ]
            ])
            ->add('category', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'
                ]
            ]);

        $builder->get('Personal')
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                /** @var Personal $data */
                $data = $event->getForm()->getData();
                $form = $event->getForm()->getParent();
                $form->add('chargePeople', EntityType::class, [
                    'class' => ChargePeople::class,
                    'required' => false,
                    'attr' => [
                        'data-plugin' => 'customselect'
                    ],
                    'multiple' => true,
                    'query_builder' => function (ChargePeopleRepository $repository) use ($data) {
                        return $repository->findPeopleByPersonalId($data?->getId());
                    },
                    'placeholder' => 'Ajouter les enfants bénéficiaire'
                ]);
            });

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var DetailRetenueForfetaire $data */
                $data = $event->getData();
                if ($data instanceof DetailRetenueForfetaire) {
                    $personal = $data->getPersonal();
                    $form->add('chargePeople', EntityType::class, [
                        'class' => ChargePeople::class,
                        'required' => false,
                        'attr' => [
                            'data-plugin' => 'customselect'
                        ],
                        'multiple' => true,
                        'query_builder' => function (ChargePeopleRepository $repository) use ($personal) {
                            return $repository->findPeopleByPersonalId($personal?->getId());
                        },
                        'placeholder' => 'Ajouter les enfants bénéficiaire',
                        'choice_attr' => function (Personal $personal) {
                            return [
                                'data-number-child' => count($personal->getChargePeople()),
                            ];
                        }
                    ]);
                }
            });

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DetailRetenueForfetaire::class,
        ]);
    }
}
