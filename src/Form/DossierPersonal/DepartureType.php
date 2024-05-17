<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Departure;
use App\Entity\DossierPersonal\Personal;
use App\Form\CustomType\DateCustomType;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\OldCongeRepository;
use App\Repository\DossierPersonal\PersonalRepository;
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
    public function __construct(private readonly CongeRepository $congeRepository, private readonly OldCongeRepository $oldCongeRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateCustomType::class)
            ->add('reason', TextType::class, [
                'disabled' => true,
            ])
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'query_builder' => function (PersonalRepository $er) {
                    return $er->findPersoBuilderForDepart();
                },
                'placeholder' => 'Sélectionner un matricule',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choice_attr' => function (Personal $personal) {
                    $historique_retour = null;
                    $last_conges = $this->congeRepository->findCongesBuilder($personal->getId(), false);
                    $historique_conge = $this->oldCongeRepository->findOneByPersoBuilder($personal->getId());
                    if ($historique_conge) {
                        $historique_retour = $historique_conge['older_retour']->format('Y-m-d');
                    }
                    $hist_date_retour = $last_conges ? $last_conges['dernier_retour']->format('Y-m-d') : $historique_retour;

                    return [
                        'data-name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                        'data-hireDate' => $personal->getContract()?->getDateEmbauche()->format('d/m/Y'),
                        'data-category' => '( ' . $personal->getCategorie()->getCategorySalarie()->getName() . ' ) - ' . $personal->getCategorie()->getIntitule(),
                        'data-dernier-retour' => $hist_date_retour === "" ? null : $hist_date_retour
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
            ->add('dateRetourConge', DateCustomType::class, [
                'required' => false,
                'attr' => [
                    'readonly' => true
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
                            'query_builder' => function (PersonalRepository $er) use ($personal) {
                                return $er->findPersoBuilderEditDepart($personal);
                            },
                            'placeholder' => 'Sélectionner un matricule',
                            'attr' => [
                                'data-plugin' => 'customselect',
                            ],
                            'choice_attr' => function (Personal $personal) {
                                $historique_retour = null;
                                $last_conges = $this->congeRepository->findCongesBuilder($personal->getId(), false);
                                $historique_conge = $this->oldCongeRepository->findOneByPersoBuilder($personal->getId());
                                if ($historique_conge) {
                                    $historique_retour = $historique_conge['older_retour']->format('Y-m-d');
                                }
                                $hist_date_retour = $last_conges ? $last_conges['dernier_retour']->format('Y-m-d') : $historique_retour;
                                return [
                                    'data-name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                                    'data-hireDate' => $personal->getContract()?->getDateEmbauche()->format('d/m/Y'),
                                    'data-category' => '( ' . $personal->getCategorie()->getCategorySalarie()->getName() . ' ) - ' . $personal->getCategorie()->getIntitule(),
                                    'data-dernier-retour' => $hist_date_retour === "" ? null : $hist_date_retour
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
