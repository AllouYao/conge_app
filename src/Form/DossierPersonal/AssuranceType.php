<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\ChargePeople;
use App\Entity\DossierPersonal\Personal;
use App\Utils\Status;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssuranceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('personal', EntityType::class, [
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
            ])
            ->add('detailRetenueForfetaires', CollectionType::class, [
                'entry_type' => DetailRetenueForfetaireType::class,
                "entry_options" => [
                    "label" => false
                ],
                'allow_add' => true,
                'allow_delete' => true,
            ]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $personal = $data?->getPersonal();
                $retenueType = $form->get('detailRetenueForfetaires')->getData();
                dd($retenueType);
                if ($retenueType === 'ASSURANCE_FAMILLE') {
                    if ($personal) {
                        $form->get('detailRetenueForfetaires')->add('chargePeople', EntityType::class, [
                            'class' => ChargePeople::class,
                            'query_builder' => function (EntityRepository $er) use ($personal) {
                                return $er->createQueryBuilder('chp')
                                    ->where('chp.personal = :personal')
                                    ->setParameter('personal', $personal);
                            },
                            'required' => false,
                            'attr' => [
                                'data-plugin' => 'customselect'
                            ],
                            'multiple' => true,
                        ]);
                    }
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}