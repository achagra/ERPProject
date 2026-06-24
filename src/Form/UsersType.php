<?php

namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label'    => 'Nom',
                'required' => !$isEdit,
                'constraints' => $isEdit ? [
                    new Length(['max' => 100]),
                ] : [
                    new NotBlank(['message' => 'Le nom est requis.']),
                    new Length(['max' => 100]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label'    => 'Prénom',
                'required' => !$isEdit,
                'constraints' => $isEdit ? [
                    new Length(['max' => 100]),
                ] : [
                    new NotBlank(['message' => 'Le prénom est requis.']),
                    new Length(['max' => 100]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label'    => 'Email',
                'required' => !$isEdit,
                'constraints' => $isEdit ? [] : [
                    new NotBlank(['message' => "L'email est requis."]),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type'     => PasswordType::class,
                'mapped'   => false,
                'required' => !$isEdit,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr'  => [
                        'placeholder' => $isEdit ? 'Laisser vide pour ne pas modifier' : '',
                    ],
                    'constraints' => $isEdit ? [] : [
                        new NotBlank(['message' => 'Le mot de passe est requis.']),
                        new Length([
                            'min'        => 6,
                            'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        ]),
                    ],
                ],
                'second_options'  => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
            ])
            ->add('telephone', TelType::class, [
                'label'    => 'Téléphone',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 20]),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label'    => 'Rôle',
                'mapped'   => false,
                'required' => true,
                'choices'  => [
                    'Admin (entreprise)' => 'ROLE_ADMIN',
                    'Super Admin'        => 'ROLE_SUPER_ADMIN',
                ],
                'data' => $options['data']?->getRole() ?? 'ROLE_ADMIN',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
            'is_edit'    => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}