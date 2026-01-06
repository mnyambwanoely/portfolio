<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter project title',
                ],
                'label' => 'Project Title',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Describe the project...',
                ],
                'label' => 'Description',
                'required' => true,
            ])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Web Development' => 'web',
                    'Networking' => 'networking',
                    'Other' => 'other',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Project Type',
                'required' => true,
            ])
            ->add('technologies', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'PHP, Symfony, JavaScript, Bootstrap... (comma separated)',
                ],
                'label' => 'Technologies (comma separated)',
                'required' => false,
            ])
            ->add('screenshotPath', FileType::class, [
                'label' => 'Project Screenshot',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF, WebP)',
                    ])
                ],
                'help' => 'Maximum file size: 5MB. Allowed formats: JPEG, PNG, GIF, WebP',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}