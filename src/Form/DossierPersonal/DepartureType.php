<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Departure;
use App\Entity\DossierPersonal\Personal;
use App\Form\CustomType\DateCustomType;
use App\Utils\Status;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepartureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateCustomType::class)
            ->add('isPaied', ChoiceType::class, [
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Avec effet financier' => true,
                    'Sans effet financier' => false
                ],
                'placeholder' => ""
            ])
            ->add('reason', TextType::class, [
                'disabled'=>true,
            ])
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'choice_label' => 'matricule',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->join('p.contract', 'ct')
                        ->leftJoin('p.departures', 'departure')
                        ->where('departure.id IS NULL ')
                        ->orderBy('p.matricule', 'ASC');
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
        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    /** @var Departure $data */
                    $data = $event->getData();
                    $form = $event->getForm();
                    $personal = $data->getPersonal();
                    if ($data instanceof Departure && $data->getId()) {
                        $form->add('personal', EntityType::class, [
                            'class' => Personal::class,
                            'choice_label' => 'matricule',
                            'query_builder' => function (EntityRepository $er) use ($personal) {
                                return $er->createQueryBuilder('p')
                                    ->join('p.contract', 'ct')
                                    ->leftJoin('p.departures', 'departure')
                                    ->andWhere('departure.id IS NOT NULL')
                                    ->andWhere('p.id = :personal')
                                    ->setParameter('personal', $personal->getId());
                            },
                            'placeholder' => 'Sélectionner un matricule',
                            'attr' => [
                                'data-plugin' => 'customselect',
                            ],
                            'choice_attr' => function (Personal $personal) {
                                return [
                                    'data-name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                                    'data-hireDate' => $personal->getContract()?->getDateEmbauche()->format('d/m/Y'),
                                    'data-category' => '( ' . $personal->getCategorie()->getCategorySalarie()->getName() . ' ) - ' . $personal->getCategorie()->getIntitule(),
                                ];
                            }
                        ]);
                    }
                }
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Departure::class,
        ]);
    }
}
