<?php
// src/Form/ContactMessageType.php

namespace App\Form;

use App\Entity\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;

class ContactMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Your Full Name',
                'required' => true,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Enter your full name',
                    'autocomplete' => 'name'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your name'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Name must be at least {{ limit }} characters',
                        maxMessage: 'Name cannot be longer than {{ limit }} characters'
                    )
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => true,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Enter your email address',
                    'autocomplete' => 'email'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your email'),
                    new Email(message: 'Please enter a valid email address')
                ]
            ])
            ->add('subject', TextType::class, [
                'label' => 'Message Subject',
                'required' => true,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'What is this regarding?'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter a subject'),
                    new Length(
                        min: 2,
                        max: 200,
                        minMessage: 'Subject must be at least {{ limit }} characters',
                        maxMessage: 'Subject cannot be longer than {{ limit }} characters'
                    )
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Your Message',
                'required' => true,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'rows' => 6,
                    'placeholder' => 'Tell me about your project or inquiry...',
                    'style' => 'resize: none;'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your message'),
                    new Length(
                        min: 5,
                        max: 2000,
                        minMessage: 'Message must be at least {{ limit }} characters',
                        maxMessage: 'Message cannot be longer than {{ limit }} characters'
                    )
                ]
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Send Message',
                'attr' => [
                    'class' => 'btn btn-primary-custom btn-lg px-5 py-3',
                    'style' => 'font-weight: 600;'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
        ]);
    }
}