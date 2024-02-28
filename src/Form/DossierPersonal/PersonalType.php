<?php

namespace App\Form\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Settings\Category;
use App\Form\CustomType\DateCustomType;
use App\Repository\Settings\CategoryRepository;
use App\Utils\Status;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                'placeholder' => 'Sélectionner un genre',
                'required' => false
            ])
            ->add('birthday', DateCustomType::class, [
                'required' => false
            ])
            ->add('lieuNaissance', TextType::class, [
                'required' => false
            ])
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
                'placeholder' => 'Sélectionner la nature de votre pièce',
                'required' => false
            ])
            ->add('refPiece', TextType::class, [
                'required' => false
            ])
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
                'query_builder' => function (CategoryRepository $categoryRepository): QueryBuilder {
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
            ->add('fonction', TextType::class, [
                'required' => false
            ])
            ->add('service', ChoiceType::class, [
                'required' => false,
                'attr' => [
                    'data-plugin' => 'customselect',
                ],
                'choices' => [
                    'STATION AP MAGIC' => Status::STATION_AP_MAGIC,
                    'STATION SHELL TREICHVILLE HABITAT' => Status::STATION_SHELL_TREICH_HABITAT,
                    'DIRECTION' => Status::DIRECTION,
                    'SHELL PARIS' => Status::SHELL_PARIS,
                    'STATION AP BENSON' => Status::STATION_AP_BENSON,
                    'STATION PO SONGON' => Status::STATION_PO_SONGON,
                    'STATION SHELL RO GABON' => Status::STATION_SHELL_RO_GABON,
                    'STATION IW YOPOUGON' => Status::STATION_IW_YOPOUGON,
                    'BOUTIQUE AGBOVILLE' => status::BOUTIQUE_AGBOVILLE
                ],
                'placeholder' => 'Sélectionner le site de travail'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personal::class,
        ]);
    }
}
