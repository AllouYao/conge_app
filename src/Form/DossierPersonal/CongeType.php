<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Conge;
use App\Entity\DossierPersonal\Personal;
use App\Form\CustomType\DateCustomType;
use App\Repository\DossierPersonal\CongeRepository;
use App\Service\CongeService;
use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CongeType extends AbstractType
{
    private CongeService $congeService;
    private CongeRepository $congeRepository;

    public function __construct(CongeService $congeService, CongeRepository $congeRepository)
    {
        $this->congeService = $congeService;
        $this->congeRepository = $congeRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDepart', DateCustomType::class)
            ->add('dateRetour', DateCustomType::class)
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'choice_label' => 'matricule',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->join('p.contract', 'ct')
                        ->leftJoin('p.conges', 'c')
                        ->leftJoin('p.departures', 'departure')
                        ->where('c.id IS NULL OR c.dateDernierRetour < :today')
                        ->andWhere('departure.id IS NULL')
                        ->setParameter('today', new \DateTime())
                        ->orderBy('p.matricule', 'ASC');
                },
                'placeholder' => 'Sélectionner un matricule',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choice_attr' => function (Personal $personal) {
                    $lastConges = $this->congeRepository->getLastCongeByID($personal->getId(), false);
                    $embauche = $personal->getContract()->getDateEmbauche();
                    $today = Carbon::today();
                    $workMonth = $this->congeService->getWorkMonth($embauche, $today);
                    return [
                        'data-name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                        'data-hireDate' => $personal->getContract()?->getDateEmbauche()->format('d/m/Y'),
                        'data-category' => '( ' . $personal->getCategorie()->getCategorySalarie()->getName() . ' ) - ' . $personal->getCategorie()->getIntitule(),
                        'data-dernier-retour' => $lastConges ? date_format($lastConges->getDateDernierRetour(), 'd/m/Y') : null,
                        'data-remaining' => $lastConges ? ceil($lastConges->getRemainingVacation()) : ceil($workMonth * 2.2 * 1.25)
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
            ->add('dernierRetour', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'

                ]
            ])
            ->add('remaining', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'

                ]
            ]);

        $builder
            ->addEventListener(
                FormEvents::SUBMIT,
                function (FormEvent $event) {
                    /** @var Conge $data */
                    $data = $event->getData();
                    $this->congeService->calculate($data);
                }
            );

        $builder
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    /** @var Conge $data */
                    $data = $event->getData();
                    $totalDay = $data->getTotalDays();
                    $vacationDay = $data->getRemainingVacation();
                    $remainingVacation = $vacationDay - $totalDay;
                    $data->setRemainingVacation($remainingVacation);
                }
            );

        $builder
            ->addEventListener(FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    /** @var Conge $data */
                    $data = $event->getData();
                    if ($data instanceof Conge && $data->getId()) {
                        $totalDay = $data->getTotalDays();
                        $day = $data->getDays();
                        $remainingVacation = $totalDay - $day;
                        $data->setRemainingVacation($remainingVacation);
                    }
                }
            );

        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    /** @var Conge $data */
                    $data = $event->getData();
                    $form = $event->getForm();
                    $personal = $data->getPersonal();
                    if ($data instanceof Conge && $data->getId()) {
                        $form->add('personal', EntityType::class, [
                            'class' => Personal::class,
                            'choice_label' => 'matricule',
                            'query_builder' => function (EntityRepository $er) use ($personal) {
                                return $er->createQueryBuilder('p')
                                    ->join('p.contract', 'ct')
                                    ->leftJoin('p.conges', 'conges')
                                    ->leftJoin('p.departures', 'departure')
                                    ->andWhere('conges.isConge = true')
                                    ->andWhere('departure.id IS NULL')
                                    ->andWhere('p.id = :personal')
                                    ->setParameter('personal', $personal->getId());
                            },
                            'placeholder' => 'Sélectionner un matricule',
                            'attr' => [
                                'data-plugin' => 'customselect',
                            ],
                            'choice_attr' => function (Personal $personal) {
                                $lastConges = $this->congeRepository->getLastCongeByID($personal->getId(), false);
                                $embauche = $personal->getContract()->getDateEmbauche();
                                $today = Carbon::today();
                                $workMonth = $this->congeService->getWorkMonth($embauche, $today);
                                return [
                                    'data-name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                                    'data-hireDate' => $personal->getContract()?->getDateEmbauche()->format('d/m/Y'),
                                    'data-category' => '( ' . $personal->getCategorie()->getCategorySalarie()->getName() . ' ) - ' . $personal->getCategorie()->getIntitule(),
                                    'data-dernier-retour' => $lastConges ? date_format($lastConges->getDateDernierRetour(), 'd/m/Y') : null,
                                    'data-remaining' => $lastConges ? ceil($lastConges->getRemainingVacation()) : ceil($workMonth * 2.2 * 1.25)
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
            'data_class' => Conge::class,
        ]);
    }
}
