<?php

namespace App\Form\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Repository\DossierPersonal\PersonalRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampagneType extends AbstractType
{
    private PersonalRepository $repositoryPer;

    public function __construct(PersonalRepository $repository)
    {
        $this->repositoryPer = $repository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startedAt', DateTimeType::class, [
                'attr' => [
                    'class' => 'form-control form-control-sm'
                ],
                'html5' => true,
                'widget' => 'single_text',
                'required' => false
            ]);

        $formModifier = static function (FormInterface $form) {

            $form
                ->add('personal', EntityType::class, [
                    'class' => Personal::class,
                    'required' => false,
                    'attr' => [
                        'data-plugin' => 'customselect'
                    ],
                    'multiple' => true,
                    'group_by' => 'categorie',
                    'help' => 'La campagne est fonction des salarié'
                ])
                ->add('checkedAll', CheckboxType::class, [
                    'mapped' => false,
                    'label_attr' => [
                        'class' => 'checkbox-inline'
                    ],
                    'label' => 'Tous sélectionner',
                    'help' => 'Coche ici et ne sélectionne aucun salarié',
                    'required' => false,
                    //'data' => $result
                ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event->getForm());
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $checkedAll = $event->getForm()->get('checkedAll')->getData();
                if ($checkedAll === true) {
                    $campagne = $event->getForm()->getData();
                    $personal = $this->repositoryPer->findAllPersonal();
                    foreach ($personal as $individual) {
                        $campagne->addPersonal($individual);
                    }
                }

            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Campagne::class
        ]);
    }
}