<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Your full name',
                ],
                'label' => 'Full Name',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Your email address',
                ],
                'label' => 'Email Address',
                'required' => true,
            ])
            ->add('subject', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Message subject',
                ],
                'label' => 'Subject',
                'required' => true,
            ])
            ->add('message', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'rows' => 6,
                    'placeholder' => 'Your message here...',
                ],
                'label' => 'Message',
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Send Message',
                'attr' => [
                    'class' => 'btn btn-primary-custom btn-lg px-5 py-3',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}