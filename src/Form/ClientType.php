<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Particulier' => 'particulier',
                    'Entreprise' => 'entreprise',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le type est requis.']),
                ],
            ])
            ->add('civilite', ChoiceType::class, [
                'label' => 'Civilite',
                'choices' => [
                    'Mr' => 'Mr',
                    'Mme' => 'Mme',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La civilite est requise.']),
                ],
            ])
            ->add('nomComplet', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom complet est requis.']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('nomEntreprise', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
            ])
            ->add('siteWeb', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
            ])
            ->add('numeroFiscale', TextType::class, [
                'label' => 'Numero fiscale',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 100]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse email est requise.']),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Numero de telephone',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 30]),
                ],
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'constraints' => [
                    new NotBlank(['message' => 'Le pays est requis.']),
                    new Length(['max' => 120]),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse est requise.']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('region', TextType::class, [
                'label' => 'Region',
                'constraints' => [
                    new NotBlank(['message' => 'La region est requise.']),
                    new Length(['max' => 120]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
