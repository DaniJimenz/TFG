<?php

namespace App\Controller;

use App\Entity\Exercise;
use App\Form\QuickEditExerciseType;
use App\Repository\ExerciseRepository;
use App\Service\ImageUploadService;
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

    #[Route('/{id}/quick-edit', name: 'quick_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function quickEdit(Exercise $exercise, Request $request, EntityManagerInterface $entityManager, ImageUploadService $imageUploadService): Response
    {
        $form = $this->createForm(QuickEditExerciseType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar carga de imagen
            $imageFile = $form->get('image_file')->getData();
            if ($imageFile) {
                $newImagePath = $imageUploadService->uploadExerciseImage($imageFile, $exercise->getUrlImage());
                $exercise->setUrlImage($newImagePath);
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

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Exercise $exercise, Request $request, EntityManagerInterface $entityManager, ImageUploadService $imageUploadService): Response
    {
        if (!$this->isCsrfTokenValid('delete_exercise_' . $exercise->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('admin_exercises_index');
        }

        if ($exercise->getTrainings()->count() > 0 || $exercise->getRoutines()->count() > 0) {
            $this->addFlash('error', 'No se puede eliminar: este ejercicio está siendo usado en rutinas o historial de usuarios.');
            return $this->redirectToRoute('admin_exercises_index');
        }

        $imageUploadService->deleteExerciseImage($exercise->getUrlImage());
        $entityManager->remove($exercise);
        $entityManager->flush();

        $this->addFlash('success', '¡Ejercicio eliminado correctamente!');
        return $this->redirectToRoute('admin_exercises_index');
    }
}
