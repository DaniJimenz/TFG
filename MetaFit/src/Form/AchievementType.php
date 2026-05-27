<?php

namespace App\Form;

use App\Entity\Achievement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class AchievementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => false,
                'constraints' => [new NotBlank(message: 'El nombre es obligatorio.')],
            ])
            ->add('description', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('type', TextType::class, [
                'label' => false,
                'constraints' => [new NotBlank(message: 'El tipo es obligatorio.')],
            ])
            ->add('judgment', TextType::class, [
                'label' => false,
                'constraints' => [new NotBlank(message: 'El identificador es obligatorio.')],
            ])
            ->add('otorgated_xp', IntegerType::class, [
                'label' => false,
                'constraints' => [new Positive(message: 'El XP debe ser mayor que 0.')],
            ])
            ->add('url_icon', TextType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('sort_order', IntegerType::class, [
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Achievement::class,
        ]);
    }
}
