<?php

namespace App\Form;

use App\Entity\Training;
use App\Entity\Exercise;
use App\Entity\Routine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class TrainingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('exercise', EntityType::class, [
                'class' => Exercise::class,
                'choice_label' => 'name',
                'label' => 'Ejercicio',
                'placeholder' => 'Selecciona un ejercicio',
            ])
            ->add('routine', EntityType::class, [
                'class' => Routine::class,
                'choice_label' => 'name',
                'label' => 'Rutina (Opcional)',
                'required' => false,
                'placeholder' => 'Selecciona una rutina',
            ])
            ->add('completedSeries', IntegerType::class, [
                'label' => 'Series Completadas',
                'attr' => ['min' => 1],
                'constraints' => [
                    new NotBlank(message: 'Las series son obligatorias'),
                    new Positive(message: 'Debes completar al menos 1 serie')
                ],
            ])
            ->add('repetitions', IntegerType::class, [
                'label' => 'Repeticiones',
                'attr' => ['min' => 1],
                'constraints' => [
                    new NotBlank(message: 'Las repeticiones son obligatorias'),
                    new Positive(message: 'Debes hacer al menos 1 repetición')
                ],
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Peso (kg)',
                'attr' => ['min' => 0, 'step' => 0.5],
                'constraints' => [
                    new NotBlank(message: 'El peso es obligatorio (usa 0 si es con peso corporal)'),
                    new PositiveOrZero(message: 'El peso no puede ser negativo')
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notas',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Training::class,
        ]);
    }
}
