<?php

namespace App\Form;

use App\Entity\Exercise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\File;

class QuickEditExerciseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'disabled' => true,
            ])
            ->add('muscular_group', ChoiceType::class, [
                'label' => 'Grupo Muscular',
                'choices' => [
                    'Pecho' => 'chest',
                    'Espalda' => 'back',
                    'Piernas' => 'legs',
                    'Hombros' => 'shoulders',
                    'Brazos' => 'arms',
                    'Core' => 'core',
                    'Glúteos' => 'glutes',
                ],
                'mapped' => true,
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Dificultad',
                'choices' => [
                    'Principiante' => 'beginner',
                    'Intermedio' => 'intermediate',
                    'Avanzado' => 'advanced',
                ],
                'mapped' => true,
            ])
            ->add('description', TextType::class, [
                'label' => 'Descripción',
                'required' => false,
                'mapped' => true,
                'attr' => ['placeholder' => 'Descripción del ejercicio...']
            ])
            ->add('image_file', FileType::class, [
                'label' => 'Imagen del Ejercicio',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Por favor, sube un archivo de imagen válido',
                    ])
                ],
                'attr' => ['accept' => 'image/*']
            ])
            ->add('url_video', TextType::class, [
                'label' => 'URL Video (YouTube, Pexels, etc)',
                'required' => false,
                'mapped' => true,
                'attr' => ['placeholder' => 'https://www.youtube.com/watch?v=... o https://videos.pexels.com/...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercise::class,
        ]);
    }
}
