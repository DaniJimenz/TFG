<?php

namespace App\Form;

use App\Entity\Exercise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\File;

class ExerciseFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre del Ejercicio',
                'attr' => ['class' => 'form-control']
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
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => ['rows' => 4]
            ])
            ->add('technique', TextareaType::class, [
                'label' => 'Técnica de Ejecución',
                'required' => false,
                'attr' => ['rows' => 4]
            ])
            ->add('url_image', FileType::class, [
                'label' => 'Imagen del Ejercicio',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Por favor sube una imagen válida (JPEG, PNG, WebP o GIF)',
                    ])
                ],
                'attr' => ['accept' => 'image/*']
            ])
            ->add('url_video', UrlType::class, [
                'label' => 'URL de Video (YouTube/Vimeo)',
                'required' => false,
                'attr' => ['placeholder' => 'https://youtube.com/watch?v=...']
            ])
            ->add('necessary_material', TextType::class, [
                'label' => 'Material Necesario',
                'required' => false,
                'attr' => ['placeholder' => 'Ej: Barra, Mancuernas, etc']
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Dificultad',
                'choices' => [
                    'Principiante' => 'Principiante',
                    'Intermedio' => 'Intermedio',
                    'Avanzado' => 'Avanzado',
                ],
            ])
            ->add('compound', CheckboxType::class, [
                'label' => '¿Ejercicio Compuesto?',
                'required' => false,
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
