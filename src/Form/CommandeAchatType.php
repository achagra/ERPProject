<?php

namespace App\Form;

use App\Entity\CommandeAchat;
use App\Entity\Entrepot;
use App\Entity\Fournisseur;
use App\Entity\Users;
use App\Repository\EntrepotRepository;
use App\Repository\FournisseurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeAchatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'choice_label' => 'nomComplet',
                'query_builder' => static function (FournisseurRepository $repository) use ($options) {
                    return $repository->createQueryBuilder('f')
                        ->andWhere('f.createdBy = :owner')
                        ->setParameter('owner', $options['owner']);
                },
                'label' => 'Fournisseur',
            ])
            ->add('dateFacture', DateType::class, [
                'label' => 'Date de facture',
                'widget' => 'single_text',
            ])
            ->add('entrepot', EntityType::class, [
                'class' => Entrepot::class,
                'choice_label' => 'nom',
                'query_builder' => static function (EntrepotRepository $repository) use ($options) {
                    return $repository->createQueryBuilder('e')
                        ->andWhere('e.createdBy = :owner')
                        ->setParameter('owner', $options['owner']);
                },
                'label' => 'Entrepôt',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes / conditions',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Ajoutez une note ou une condition si nécessaire',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommandeAchat::class,
            'owner' => null,
        ]);
        $resolver->setAllowedTypes('owner', [Users::class, 'null']);
    }
}