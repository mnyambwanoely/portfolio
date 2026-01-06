<?php
// src/Form/PersonalDetailsType.php

namespace App\Form;

use App\Entity\PersonalDetails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PersonalDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Full Name
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your full name',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Full name is required']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Full name must be at least {{ limit }} characters',
                        'maxMessage' => 'Full name cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            
            // Job Title
            ->add('jobTitle', TextType::class, [
                'label' => 'Job Title/Position',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., IT Specialist, Web Developer'
                ]
            ])
            
            // Email
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'your.email@example.com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Email address is required'])
                ]
            ])
            
            // Phone Number
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+255 123 456 789'
                ]
            ])
            
            // Alternative Phone
            ->add('phone2', TextType::class, [
                'label' => 'Alternative Phone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+255 987 654 321'
                ]
            ])
            
            // Location
            ->add('location', TextType::class, [
                'label' => 'Location/City',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Dar es Salaam, Tanzania'
                ]
            ])
            
            // Address
            ->add('address', TextareaType::class, [
                'label' => 'Full Address',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Your complete address...'
                ]
            ])
            
            // Professional Summary
            ->add('professionalSummary', TextareaType::class, [
                'label' => 'Professional Summary',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Describe your professional background, skills, and experience...'
                ]
            ])
            
            // Years of Experience
            ->add('yearsOfExperience', ChoiceType::class, [
                'label' => 'Years of Experience',
                'required' => false,
                'choices' => [
                    'Less than 1 year' => '0',
                    '1-2 years' => '1-2',
                    '3-5 years' => '3-5',
                    '5-10 years' => '5-10',
                    '10+ years' => '10+'
                ],
                'placeholder' => 'Select years of experience',
                'attr' => ['class' => 'form-select']
            ])
            
            // Save Button
            ->add('save', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Update Details' : 'Save Details',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg px-5',
                    'style' => 'font-weight: 600;'
                ]
            ])
            
            // Save and Continue Button (for edit)
            ->add('saveAndContinue', SubmitType::class, [
                'label' => 'Save & Continue Editing',
                'attr' => [
                    'class' => 'btn btn-outline-primary btn-lg px-5',
                    'style' => 'font-weight: 600;'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PersonalDetails::class,
            'is_edit' => false, // Custom option to check if we're editing
        ]);
        
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}