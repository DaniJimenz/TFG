<?php

// src/Controller/LibraryController.php
namespace App\Controller;

use App\Entity\Exercise;
use App\Form\ExerciseFormType;
use App\Repository\ExerciseRepository;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/library', name: 'app_library_')]
class LibraryController extends AbstractController
{
    public function __construct(private ImageUploadService $imageUploadService) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ExerciseRepository $exerciseRepository): Response
    {
        // Traemos todos los ejercicios de la BD
        $exercises = $exerciseRepository->findAll();

        return $this->render('library/index.html.twig', [
            'exercises' => $exercises,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $exercise = new Exercise();
        $form = $this->createForm(ExerciseFormType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Procesar imagen subida
            $imageFile = $form->get('url_image')->getData();
            if ($imageFile) {
                $imagePath = $this->imageUploadService->uploadExerciseImage($imageFile);
                $exercise->setUrlImage($imagePath);
            }

            $entityManager->persist($exercise);
            $entityManager->flush();

            $this->addFlash('success', '¡Ejercicio creado exitosamente!');
            return $this->redirectToRoute('app_library_index');
        }

        return $this->render('library/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Exercise $exercise, Request $request, EntityManagerInterface $entityManager): Response
    {
        $oldImagePath = $exercise->getUrlImage();
        
        $form = $this->createForm(ExerciseFormType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Procesar imagen subida
            $imageFile = $form->get('url_image')->getData();
            if ($imageFile) {
                $imagePath = $this->imageUploadService->uploadExerciseImage($imageFile, $oldImagePath);
                $exercise->setUrlImage($imagePath);
            }

            $entityManager->flush();

            $this->addFlash('success', '¡Ejercicio actualizado exitosamente!');
            return $this->redirectToRoute('app_library_index');
        }

        return $this->render('library/edit.html.twig', [
            'form' => $form,
            'exercise' => $exercise,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Exercise $exercise, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_exercise_' . $exercise->getId(), $request->request->get('_token'))) {
            // Prevenir Error 500: No borrar si ya ha sido usado por usuarios
            if ($exercise->getTrainings()->count() > 0 || $exercise->getRoutines()->count() > 0) {
                $this->addFlash('error', 'No se puede eliminar: Hay usuarios que ya están utilizando este ejercicio en sus rutinas o historial.');
                return $this->redirectToRoute('app_library_index');
            }

            // Eliminar imagen asociada
            $this->imageUploadService->deleteExerciseImage($exercise->getUrlImage());
            
            $entityManager->remove($exercise);
            $entityManager->flush();

            $this->addFlash('success', '¡Ejercicio eliminado exitosamente!');
        }

        return $this->redirectToRoute('app_library_index');
    }
}
