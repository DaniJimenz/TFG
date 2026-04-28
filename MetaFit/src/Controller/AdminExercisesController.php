<?php

namespace App\Controller;

use App\Entity\Exercise;
use App\Form\QuickEditExerciseType;
use App\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/exercises', name: 'admin_exercises_')]
#[IsGranted('ROLE_ADMIN')]
class AdminExercisesController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ExerciseRepository $exerciseRepository): Response
    {
        $exercises = $exerciseRepository->findAll();

        return $this->render('admin/exercises/index.html.twig', [
            'exercises' => $exercises,
        ]);
    }

    #[Route('/{id}/quick-edit', name: 'quick_edit', methods: ['GET', 'POST'])]
    public function quickEdit(Exercise $exercise, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuickEditExerciseType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Obtener URLs del formulario (no mapeado)
            $imageUrl = $form->get('url_image')->getData();
            $videoUrl = $form->get('url_video')->getData();

            // Actualizar solo si se proporcionan
            if ($imageUrl) {
                $exercise->setUrlImage($imageUrl);
            }
            if ($videoUrl) {
                $exercise->setUrlVideo($videoUrl);
            }

            $entityManager->flush();

            $this->addFlash('success', '¡Ejercicio actualizado!');
            return $this->redirectToRoute('admin_exercises_index');
        }

        return $this->render('admin/exercises/quick_edit.html.twig', [
            'exercise' => $exercise,
            'form' => $form,
        ]);
    }
}
