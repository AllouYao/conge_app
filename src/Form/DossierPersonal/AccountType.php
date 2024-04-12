<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Utils\Status;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AccountType extends AbstractType
{


    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('personal', EntityType::class, [
                'class' => Personal::class,
                'query_builder' => function (EntityRepository $er) {
                    if ($this->authorizationChecker->isGranted('ROLE_RH')) {
                        return $er->createQueryBuilder('p')
                            ->join('p.contract', 'contract')
                            ->leftJoin('p.departures', 'departures')
                            ->where('p.modePaiement in (:modePaiement)')
                            ->andWhere('departures.id IS NULL')
                            ->andWhere('p.active = true')
                            ->andWhere('contract.typeContrat IN (:type)')
                            ->setParameter('modePaiement', [Status::VIREMENT, Status::CHEQUE])
                            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
                            ->orderBy('p.matricule', 'ASC');
                    } else {
                        return $er->createQueryBuilder('p')
                            ->join('p.contract', 'contract')
                            ->join('p.categorie', 'category')
                            ->join('category.categorySalarie', 'category_salarie')
                            ->leftJoin('p.departures', 'departures')
                            ->where('p.modePaiement in (:modePaiement)')
                            ->andWhere('departures.id IS NULL')
                            ->andWhere('p.active = true')
                            ->andWhere('contract.typeContrat IN (:type)')
                            ->andWhere("category_salarie.code IN (:code)")
                            ->setParameter('modePaiement', [Status::VIREMENT, Status::CHEQUE])
                            ->setParameter('type', [Status::CDD, Status::CDI, Status::CDDI])
                            ->setParameter('code', ['OUVRIER / EMPLOYES', 'CHAUFFEURS'])
                            ->orderBy('p.matricule', 'ASC');
                    }
                },
                'placeholder' => 'Sélectionner un salarié',
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
            ])
            ->add('accountBanks', CollectionType::class, [
                'entry_type' => AccountBankType::class,
                "entry_options" => [
                    "label" => false
                ],
                'allow_add' => true,
                'allow_delete' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
