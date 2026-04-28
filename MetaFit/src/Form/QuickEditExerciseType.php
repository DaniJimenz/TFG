<?php

namespace App\Form;

use App\Entity\Exercise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class QuickEditExerciseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'disabled' => true,
            ])
            ->add('url_image', UrlType::class, [
                'label' => 'URL Imagen (Pexels, Unsplash, etc)',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'https://images.pexels.com/...']
            ])
            ->add('url_video', UrlType::class, [
                'label' => 'URL Video (YouTube, Pexels, etc)',
                'required' => false,
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
