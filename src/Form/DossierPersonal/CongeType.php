<?php

namespace App\Form\DossierPersonal;

use DateTime;
use Carbon\Carbon;
use App\Service\CongeService;
use Doctrine\ORM\EntityRepository;
use App\Entity\DossierPersonal\Conge;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Form\CustomType\DateCustomType;
use App\Entity\DossierPersonal\Personal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\DossierPersonal\CongeRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

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
            ->add('typeConge', ChoiceType::class, [
                'choices' => [
                    'Effectif' => "Effectif",
                    'Partiel' => "Partiel",
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => [
                    'class' => 'radio-inline'
                ],
                "data"=>"Effectif"
                //'required' => false
            ])
            ->add('typePayementConge', ChoiceType::class, [
                'choices' => [
                    'Immédiat' => "Immédiat",
                    'Ultérieur' => "Ultérieur",
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => [
                    'class' => 'radio-inline'
                ],
                "data"=>"Immédiat"

                //'required' => false
            ])
            ->add('dateDepart', DateCustomType::class,)
            ->add('dateReprise', DateCustomType::class,)
            ->add('dateRetour', DateCustomType::class,[
            ])
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                 'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->join('p.contract', 'ct')
                        ->leftJoin('p.conges', 'c')
                        ->leftJoin('p.departures', 'departure')
                        ->where('c.id IS NULL OR c.dateDernierRetour < :today AND c.isConge = false ')
                        ->andWhere('departure.id IS NULL') 
                        ->setParameter('today', new DateTime())
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
                    $personal = $data->getPersonal();
                    $lastConges = $this->congeRepository->getLastCongeByID($personal->getId(), false);
                    if ($lastConges)
                        $this->congeService->congesPayerByLast($data);
                    $this->congeService->congesPayerByFirst($data);
                }
            );

        $builder
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    /** @var Conge $data */
                    $data = $event->getData();
                    $totalDay = (int)$data->getTotalDays();
                    $vacationDay = (int)$data->getDays();
                    $remainingVacation = $totalDay - $vacationDay;
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
