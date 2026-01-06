<?php

namespace App\Form;

use App\Entity\Skill;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SkillType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter skill name',
                ],
                'label' => 'Skill Name',
                'required' => true,
            ])
            ->add('percentage', IntegerType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100,
                ],
                'label' => 'Percentage (0-100)',
                'required' => true,
            ])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Technical' => 'technical',
                    'Soft Skills' => 'soft',
                    'Design' => 'design',
                    'Language' => 'language',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Category',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Skill::class,
        ]);
    }
}