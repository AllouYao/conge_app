<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Conge;
use App\Entity\DossierPersonal\Personal;
use App\Form\CustomType\DateCustomType;
use App\Service\CongeService;
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

    public function __construct(CongeService $congeService)
    {
        $this->congeService = $congeService;
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
                        ->join('p.contract', 'ct');
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
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    /** @var Conge $data */
                    $data = $event->getData();
                    if ($data instanceof Conge) {
                        $workMonth = $data->getWorkMonths();
                        $vacationDay = $workMonth * 2.2 * 1.25;
                        $totalDay = $data->getTotalDays();
                        $remainingVacation = $vacationDay - $totalDay;
                        $data->setRemainingVacation($remainingVacation);
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
