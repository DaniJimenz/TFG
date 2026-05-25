<?php

namespace App\Form;

use App\Entity\Exercise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
                    'Pecho' => 'Pecho',
                    'Espalda' => 'Espalda',
                    'Piernas' => 'Piernas',
                    'Hombros' => 'Hombros',
                    'Brazos' => 'Brazos',
                    'Core' => 'Core',
                    'Antebrazos' => 'Antebrazos',
                    'Glúteos' => 'Glúteos',
                ],
                'mapped' => true,
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Dificultad',
                'choices' => [
                    'Baja' => 'Baja',
                    'Media' => 'Media',
                    'Alta' => 'Alta',
                ],
                'mapped' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'mapped' => true,
                'attr' => ['placeholder' => 'Descripción del ejercicio...', 'rows' => 3]
            ])
            ->add('url_image', TextType::class, [
                'label' => 'URL de Imagen',
                'required' => false,
                'mapped' => true,
                'attr' => ['placeholder' => 'https://...']
            ])
            ->add('image_file', FileType::class, [
                'label' => 'Subir nueva imagen (opcional)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        mimeTypesMessage: 'Por favor, sube un archivo de imagen válido',
                    )
                ],
                'attr' => ['accept' => 'image/*']
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
