<?php

namespace App\Form;

use App\Entity\Employe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis.']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est requis.']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => "L'email est requis."]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label'    => 'Téléphone',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 20]),
                ],
            ])
            ->add('departement', ChoiceType::class, [
                'label'   => 'Département',
                'choices' => [
                    'RH'               => 'RH',
                    'Service de vente' => 'Service de vente',
                    'Comptabilité'     => 'Comptabilité',
                    'Informatique'     => 'Informatique',
                    'Logistique'       => 'Logistique',
                    'Autre'            => 'Autre',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le département est requis.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employe::class,
        ]);
    }
}