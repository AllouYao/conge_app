<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\ChargePeople;
use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\PersonalRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function Doctrine\ORM\QueryBuilder;

class ChargeType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'query_builder' => function (PersonalRepository $er) {
                    return $er->findPersonalWithChargePeople();
                },
                'placeholder' => '--- Sélectionner un salarié ---',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choice_attr' => function (Personal $personal) {
                    return [
                        'data-id' => $personal->getId(),
                        'data-name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                        'data-hireDate' => $personal->getContract()?->getDateEmbauche()->format('d/m/Y'),
                        'data-category' => '( ' . $personal->getCategorie()->getCategorySalarie()->getName() . ' ) - ' . $personal->getCategorie()
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
            ->add('chargePeople', CollectionType::class, [
                "entry_type" => ChargePeopleType::class,
                "allow_add" => true,
                "allow_delete" => true,
                "entry_options" => [
                    "label" => false
                ]
            ]);

        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    /** @var ChargePeople $data */
                    $data = $event->getData();
                    $form = $event->getForm();
                    $personal = $data['personal'] ?? null;
                    $arrayCharge = $data['chargePeople'] ?? null;
                    if ($arrayCharge) {
                        foreach ($data['chargePeople'] as $chargePerson) {
                            if ($chargePerson instanceof ChargePeople && $chargePerson->getId()
                            ) {
                                $form->add('personal', EntityType::class, [
                                    'class' => Personal::class,
                                    'query_builder' => function (PersonalRepository $er) use ($personal) {
                                        return $er->findEditPersonalWithChargePeople($personal);
                                    },
                                    'placeholder' => '--- Sélectionner un matricule ---',
                                    'attr' => [
                                        'data-plugin' => 'customselect',
                                    ],
                                    'choice_attr' => function (Personal $personal) {
                                        return [
                                            'data-id' => $personal->getId(),
                                            'data-name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                                            'data-hireDate' => $personal->getContract()?->getDateEmbauche()->format('d/m/Y'),
                                            'data-category' => '( ' . $personal->getCategorie()->getCategorySalarie()->getName() . ' ) - ' . $personal->getCategorie()
                                        ];
                                    }
                                ]);
                            }
                        }
                    }
                }
            );
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

        ]);
    }
}