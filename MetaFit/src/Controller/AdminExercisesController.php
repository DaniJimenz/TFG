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
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/exercises', name: 'admin_exercises_')]
#[IsGranted('ROLE_ADMIN')]
class AdminExercisesController extends AbstractController
{
    private const UPLOAD_DIR = 'uploads/exercises';

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ExerciseRepository $exerciseRepository): Response
    {
        $exercises = $exerciseRepository->findAll();

        return $this->render('admin/exercises/index.html.twig', [
            'exercises' => $exercises,
        ]);
    }

    #[Route('/{id}/quick-edit', name: 'quick_edit', methods: ['GET', 'POST'])]
    public function quickEdit(Exercise $exercise, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(QuickEditExerciseType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar carga de imagen
            $imageFile = $form->get('image_file')->getData();
            if ($imageFile) {
                $newFilename = $this->uploadImage($imageFile, $slugger);
                $exercise->setUrlImage('/uploads/exercises/' . $newFilename);
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

    private function uploadImage(UploadedFile $file, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/' . self::UPLOAD_DIR,
                $newFilename
            );
        } catch (\Exception $e) {
            throw new \Exception('No se pudo guardar la imagen: ' . $e->getMessage());
        }

        return $newFilename;
    }
}
