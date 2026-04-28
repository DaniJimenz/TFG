<?php

namespace App\Controller;

use App\Entity\Training;
use App\Entity\Exercise;
use App\Entity\Routine;
use App\Form\TrainingFormType;
use App\Repository\TrainingRepository;
use App\Repository\ExerciseRepository;
use App\Repository\RoutineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainings', name: 'training_')]
#[IsGranted('ROLE_USER')]
class TrainingController extends AbstractController
{
    /**
     * Listar todos los entrenamientos del usuario
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TrainingRepository $trainingRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $trainings = $trainingRepository->findBy(
            ['appUser' => $user],
            ['date' => 'DESC']
        );

        return $this->render('training/index.html.twig', [
            'trainings' => $trainings,
        ]);
    }

    /**
     * Ver detalle de un entrenamiento
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Training $training): Response
    {
        if ($training->getAppUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this training.');
        }

        return $this->render('training/show.html.twig', [
            'training' => $training,
        ]);
    }

    /**
     * Registrar nuevo entrenamiento
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $training = new Training();
        $form = $this->createForm(TrainingFormType::class, $training);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $training->setAppUser($user);
            $training->setDate(new \DateTimeImmutable());

            // Calcular 1RM estimado (Epley formula: weight * (1 + reps/30))
            $oneRm = $training->getWeight() * (1 + ($training->getRepetitions() / 30));
            $training->setOneRmEstimated($oneRm);

            $entityManager->persist($training);
            $entityManager->flush();

            $this->addFlash('success', '¡Entrenamiento registrado exitosamente!');

            return $this->redirectToRoute('training_index');
        }

        return $this->render('training/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Editar entrenamiento
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Training $training,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($training->getAppUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this training.');
        }

        $form = $this->createForm(TrainingFormType::class, $training);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalcular 1RM
            $oneRm = $training->getWeight() * (1 + ($training->getRepetitions() / 30));
            $training->setOneRmEstimated($oneRm);

            $entityManager->flush();

            $this->addFlash('success', '¡Entrenamiento actualizado exitosamente!');

            return $this->redirectToRoute('training_index');
        }

        return $this->render('training/edit.html.twig', [
            'training' => $training,
            'form' => $form,
        ]);
    }

    /**
     * Eliminar entrenamiento
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Training $training, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($training->getAppUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this training.');
        }

        if ($this->isCsrfTokenValid('delete_training_' . $training->getId(), $request->request->get('_token'))) {
            $entityManager->remove($training);
            $entityManager->flush();

            $this->addFlash('success', 'Training deleted successfully!');
        }

        return $this->redirectToRoute('training_index');
    }

    /**
     * Progreso del usuario - gráficos y estadísticas
     */
    #[Route('/progress', name: 'progress', methods: ['GET'])]
    public function progress(TrainingRepository $trainingRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Entrenamientos de los últimos 30 días
        $thirtyDaysAgo = new \DateTimeImmutable('-30 days');
        $recentTrainings = $trainingRepository->findTrainingsAfterDate($user, $thirtyDaysAgo);

        // Estadísticas
        $stats = [
            'total_trainings' => count($recentTrainings),
            'completed_trainings' => 0,
            'total_weight_lifted' => 0,
            'total_duration' => 0,
            'avg_weight' => 0,
        ];

        $completedCount = 0;
        foreach ($recentTrainings as $training) {
            if ($training->isCompleted()) {
                $completedCount++;
            }
            $stats['total_weight_lifted'] += ($training->getWeight() * $training->getRepetitions() * $training->getCompletedSeries());
            $stats['total_duration'] += $training->getDurationMinutes();
        }

        $stats['completed_trainings'] = $completedCount;
        if ($stats['total_trainings'] > 0) {
            $stats['avg_weight'] = round($stats['total_weight_lifted'] / $stats['total_trainings'], 2);
        }

        // Agrupar entrenamientos por ejercicio para mostrar los más trabajados
        $trainingsByExercise = [];
        foreach ($recentTrainings as $training) {
            $exerciseId = $training->getExercise()->getId();
            if (!isset($trainingsByExercise[$exerciseId])) {
                $trainingsByExercise[$exerciseId] = [
                    'exercise' => $training->getExercise(),
                    'count' => 0,
                    'pr' => 0,
                    'avg' => 0,
                    'total_weight' => 0,
                ];
            }
            
            $weight = $training->getWeight();
            $trainingsByExercise[$exerciseId]['count']++;
            $trainingsByExercise[$exerciseId]['total_weight'] += $weight;
            
            if ($weight > $trainingsByExercise[$exerciseId]['pr']) {
                $trainingsByExercise[$exerciseId]['pr'] = $weight;
            }
        }

        // Calcular promedios
        foreach ($trainingsByExercise as &$exercise) {
            $exercise['avg'] = $exercise['count'] > 0 ? $exercise['total_weight'] / $exercise['count'] : 0;
        }

        // Ordenar por cantidad de veces realizado
        usort($trainingsByExercise, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return $this->render('training/progress.html.twig', [
            'stats' => $stats,
            'exercises' => $trainingsByExercise,
            'recentTrainings' => $recentTrainings,
        ]);
    }

    /**
     * Historial de un ejercicio específico
     */
    #[Route('/exercise/{exerciseId}/history', name: 'exercise_history', methods: ['GET'])]
    public function exerciseHistory(
        int $exerciseId,
        TrainingRepository $trainingRepository,
        ExerciseRepository $exerciseRepository
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $exercise = $exerciseRepository->find($exerciseId);
        if (!$exercise) {
            throw $this->createNotFoundException('Exercise not found');
        }

        $trainings = $trainingRepository->findByUserAndExercise($user, $exercise);

        return $this->render('training/exercise_history.html.twig', [
            'exercise' => $exercise,
            'trainings' => $trainings,
        ]);
    }

    /**
     * Resumen semanal de entrenamientos
     */
    #[Route('/weekly-summary', name: 'weekly_summary', methods: ['GET'])]
    public function weeklySummary(TrainingRepository $trainingRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $weekTrainings = $trainingRepository->findTrainingsAfterDate($user, $sevenDaysAgo);

        return $this->render('training/weekly_summary.html.twig', [
            'weekly_summary' => $weekTrainings,
        ]);
    }
}
