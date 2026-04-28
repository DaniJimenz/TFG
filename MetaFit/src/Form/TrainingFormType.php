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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

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
            ])
            ->add('repetitions', IntegerType::class, [
                'label' => 'Repeticiones',
                'attr' => ['min' => 1],
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Peso (kg)',
                'attr' => ['min' => 0, 'step' => 0.5],
            ])
            ->add('durationMinutes', IntegerType::class, [
                'label' => 'Duración (minutos)',
                'attr' => ['min' => 1],
            ])
            ->add('completed', CheckboxType::class, [
                'label' => 'Entrenamiento Completado',
                'required' => false,
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
