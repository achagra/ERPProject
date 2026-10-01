<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Client;
use App\Entity\Fournisseur;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('assetType', ChoiceType::class, [
                'label' => "Types d'actifs",
                'choices' => [
                    'Matière première' => 'matiere_premiere',
                    'Composite / assemblage' => 'composite_assemblage',
                    'Service' => 'service',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix de base',
                'currency' => 'USD',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
                'required' => false,
                'label' => 'Catégorie',
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => static fn (Client $client): string => $client->getNomComplet() . ' - ' . $client->getNomEntreprise(),
                'placeholder' => 'Choisir un client',
                'required' => false,
                'label' => 'Client associé',
            ])
            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'choice_label' => static fn (Fournisseur $fournisseur): string => $fournisseur->getNomComplet() . ' - ' . $fournisseur->getNomEntreprise(),
                'placeholder' => 'Choisir un fournisseur',
                'required' => false,
                'label' => 'Fournisseur associé',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'required' => false,
                'attr' => [
                    'rows' => 8,
                    'placeholder' => 'Décrivez ici les caractéristiques, l\'usage, les contraintes et toute information utile.',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
