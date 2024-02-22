<?php

namespace App\Form\DossierPersonal;

use App\Utils\Status;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Settings\Category;
use App\Form\CustomType\DateCustomType;
use App\Entity\DossierPersonal\Personal;
use Symfony\Component\Form\AbstractType;
use App\Repository\Settings\CategoryRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PersonalType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matricule')
            ->add('firstName')
            ->add('lastName')
            ->add('genre', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Masculin' => Status::MASCULIN,
                    'Féminin' => Status::FEMININ
                ],
                'placeholder' => 'Sélectionner un genre'
            ])
            ->add('birthday', DateCustomType::class)
            ->add('lieuNaissance')
            ->add('refCNPS')
            ->add('piece', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Passeport' => Status::PASSPORT,
                    'CNI' => Status::CNI,
                    'Carte consulaire' => Status::CARTE_CONSULAIRE,
                    'Attestation' => Status::ATTESTATION
                ],
                'placeholder' => 'Sélectionner la nature de votre pièce'
            ])
            ->add('refPiece')
            ->add('address', TextType::class, [
                'required' => false
            ])
            ->add('telephone', TextType::class, [
                'required' => false
            ])
            ->add('email', TextType::class, [
                'required' => false
            ])
            ->add('categorie', EntityType::class, [
                'class' => Category::class,
                'placeholder' => 'Sélectionner une catégorie salariale',
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choice_attr' => function (Category $category) {
                    return [
                        'data-amount' => $category->getAmount()
                    ];
                },
                'required' => true,
                'group_by' => 'categorySalarie',
                'query_builder' => function (CategoryRepository $categoryRepository):QueryBuilder { 
                    return $categoryRepository->findCategorieByEmploye();
                }
            ])
            ->add('conjoint', TextType::class, [
                'required' => false
            ])
            ->add('numCertificat', TextType::class, [
                'required' => false
            ])
            ->add('numExtraitActe', TextType::class, [
                'required' => false
            ])
            ->add('etatCivil', ChoiceType::class, [
                'choices' => [
                    'Célibataire' => Status::CELIBATAIRE,
                    'Divorcé (e)' => Status::DIVORCE,
                    'Marié (e)' => Status::MARIEE,
                    'Veuf (ve)' => Status::VEUF,
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => [
                    'class' => 'radio-inline'
                ],
                //'required' => false
            ])
            ->add('niveauFormation', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Bac ' => Status::BAC,
                    'BTS (Bac + 2)' => Status::BTS,
                    'Maitrise (Bac + 4) ' => Status::MAITRISE,
                    'Master (Bac + 5)' => Status::Master
                ],
                'placeholder' => 'Sélectionner votre niveau de formation',
                'required' => false
            ])
            ->add('contract', ContractType::class, [
                'required' => false
            ])
            ->add('salary', SalaryType::class, [
                'label' => false,
            ])
            ->add('modePaiement', ChoiceType::class, [
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'Chèque' => Status::CHEQUE,
                    'Virement' => Status::VIREMENT,
                    'Caisse' => Status::CAISSE,
                ],
                'placeholder' => 'Sélectionner le mode de paiement'
            ])
            ->add('fonction')
            ->add('service')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personal::class,
        ]);
    }
}
