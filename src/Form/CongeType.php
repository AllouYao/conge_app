<?php

namespace App\Form;

use DateTime;
use App\Utils\Status;
use Doctrine\ORM\EntityRepository;
use App\Entity\Conge;
use App\Form\CustomType\DateCustomType;
use App\Entity\Personal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class CongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


        $builder
            ->add('typePayementConge', ChoiceType::class, [
                'choices' => [
                    'Immédiat' => Status::IMMEDIAT,
                    'Ultérieur' => Status::ULTERIEUR,
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => [
                    'class' => 'radio-inline'
                ],
                "data" => "Immédiat"
            ])
            ->add('days', TextType::class, [
                'attr' => [
                    'class' => 'separator text-end',
                    'readonly' => 'readonly'

                ],
                'required' => false,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('congeReste', TextType::class, [
                'attr' => [
                    'class' => 'separator text-end',
                    'readonly' => 'readonly'
                ],
                'mapped'=>false,
                'required' => false,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('dateDepart', DateCustomType::class,)
            ->add('dateRetour', DateCustomType::class)
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->join('p.contract', 'ct')
                        ->join('p.categorie', 'category')
                        ->join('category.categorySalarie', 'category_salarie')
                        ->leftJoin('p.departures', 'departures')
                        ->leftJoin('p.conges', 'c')
                        ->where('c.id IS NULL OR c.dateDernierRetour < :today AND c.isConge = false ')
                        ->andWhere('departures.id IS NULL')
                        ->andWhere('p.active = true')
                        ->andWhere('ct.typeContrat IN (:type)')
                        ->andWhere("category_salarie.code IN (:code)")
                        ->setParameter('today', new DateTime())
                        ->setParameter('type', [Status::CDI, Status::CDDI, Status::CDD])
                        ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
                        ->orderBy('p.matricule', 'ASC');

                },
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
            ])
            ->add('dateReprise', DateCustomType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conge::class,
        ]);
    }
}
