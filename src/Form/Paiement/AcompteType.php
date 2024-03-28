<?php

namespace App\Form\Paiement;

use App\Entity\DevPaie\Operation;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Entity\User;
use App\Form\CustomType\DateCustomType;
use App\Utils\Status;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcompteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeOperations',ChoiceType::class,[
                'choices' => [
                    'Prêt' => Status::PRET,
                    'Acompte' => Status::ACOMPTE
                ],
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'placeholder' => 'Sélectionner un type'
            ])
            ->add('amountMensualite', TextType::class,[
                'attr' => [
                    'class' => 'text-end separator',
                    'readonly' => true
                ]
            ])
            ->add('nbMensualite')
            ->add('dateOperation', DateCustomType::class)
            ->add('amount', TextType::class,[
                'attr' => [
                    'class' => 'text-end separator'
                ]
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
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'choice_label' => 'matricule',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->join('p.contract', 'ct')
                        ->leftJoin('p.departures', 'departures')
                        ->where('departures.id IS NULL');
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Operation::class,
        ]);
    }
}
