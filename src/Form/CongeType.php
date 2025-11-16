<?php

namespace App\Form;

use DateTime;
use App\Entity\Conge;
use App\Entity\Personal;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Form\CustomType\DateCustomType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class CongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDepart', DateCustomType::class,)
            ->add('dateRetour', DateCustomType::class)
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'placeholder' => 'Sélectionner un salarié',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
            ])
            ->add('name', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'
                ]
            ])
            ->add('totalDays', HiddenType::class, [
            ])
           
            ->add('category', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'readonly' => 'readonly'
                ]
            ]);

        // Calculer totalDays avant la soumission du formulaire
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            
            if (isset($data['dateDepart']) && isset($data['dateRetour'])) {
                $dateDepart = \DateTime::createFromFormat('Y-m-d', $data['dateDepart']);
                $dateRetour = \DateTime::createFromFormat('Y-m-d', $data['dateRetour']);
                
                if ($dateDepart && $dateRetour && $dateRetour >= $dateDepart) {
                    $diff = $dateRetour->diff($dateDepart);
                    $totalDays = $diff->days + 1; // +1 pour inclure le jour de départ et le jour de retour
                    $data['totalDays'] = number_format($totalDays, 2, '.', '');
                    $event->setData($data);
                }
            }
        });

        // Calculer totalDays lors du chargement des données (pour l'édition)
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $conge = $event->getData();
            
            if ($conge && $conge->getDateDepart() && $conge->getDateRetour()) {
                $dateDepart = $conge->getDateDepart();
                $dateRetour = $conge->getDateRetour();
                
                if ($dateRetour >= $dateDepart) {
                    $diff = $dateRetour->diff($dateDepart);
                    $totalDays = $diff->days + 1;
                    $conge->setTotalDays(number_format($totalDays, 2, '.', ''));
                }
            }
        });

        // Recalculer totalDays lorsque les dates changent
        $calculateTotalDays = function (FormEvent $event) use ($builder): void {
            $form = $event->getForm()->getParent();
            if (!$form) {
                return;
            }

            $dateDepart = $form->get('dateDepart')->getData();
            $dateRetour = $form->get('dateRetour')->getData();

            if ($dateDepart && $dateRetour && $dateRetour >= $dateDepart) {
                $diff = $dateRetour->diff($dateDepart);
                $totalDays = $diff->days + 1;
                
                $conge = $form->getData();
                if ($conge) {
                    $conge->setTotalDays(number_format($totalDays, 2, '.', ''));
                }
            }
        };

        $builder->get('dateDepart')->addEventListener(FormEvents::POST_SUBMIT, $calculateTotalDays);
        $builder->get('dateRetour')->addEventListener(FormEvents::POST_SUBMIT, $calculateTotalDays);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conge::class,
        ]);
    }
}
