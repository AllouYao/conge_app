<?php


namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\HeureSup;
use App\Form\CustomType\DateCustomType;
use App\Repository\Settings\TauxHoraireRepository;
use App\Utils\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HeureSupType extends AbstractType
{
    private TauxHoraireRepository $horaireRepository;

    public function __construct(TauxHoraireRepository $horaireRepository)
    {
        $this->horaireRepository = $horaireRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startedDate', DateCustomType::class)
            ->add('endedDate', DateCustomType::class)
            ->add('startedHour', TimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('endedHour', TimeType::class, [
                'widget' => 'single_text',
            ])
            ->add(
                'typeDay',
                ChoiceType::class,
                [
                    'choices' => [
                        'Ouvrable' => Status::NORMAL,
                        'Dimanche ou férié' => Status::DIMANCHE_FERIE,
                    ],
                    'placeholder' => 'Sélectionner le type de journée',
                    'label' => 'Type',
                    'multiple' => false,
                    'expanded' => false,
                    'attr' => [
                        'class' => 'form-select form-select-sm select2',
                    ],
                ]
            )
            ->add(
                'typeJourOrNuit',
                ChoiceType::class,
                [
                    'choices' => [
                        'Jour' => Status::JOUR,
                        'Nuit' => Status::NUIT,
                    ],
                    'placeholder' => 'Sélectionner la période de la journée',
                    'label' => 'Type',
                    'multiple' => false,
                    'expanded' => false,
                    'attr' => [
                        'class' => 'form-select form-select-sm select2',
                    ],
                ]
            )
            ->add('tauxHoraire', HiddenType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'separator'
                ]
            ]);
        $builder
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    /** @var HeureSup $data */
                    $data = $event->getData();
                    $tauxHoraire = $this->horaireRepository->active();
                    $data?->setTauxHoraire($tauxHoraire?->getAmount());
                }
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HeureSup::class,
        ]);
    }
}