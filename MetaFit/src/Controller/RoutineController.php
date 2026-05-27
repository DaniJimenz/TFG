<?php

namespace App\Controller;

use App\Entity\Routine;
use App\Entity\Exercise;
use App\Service\RoutineService;
use App\Service\AchievementService;
use App\Repository\RoutineRepository;
use App\Repository\ExerciseRepository;
use App\Repository\TrainingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/routines', name: 'routine_')]
#[IsGranted('ROLE_USER')]
class RoutineController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Listar rutinas del usuario
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(RoutineRepository $routineRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $routines = $routineRepository->findBy(
            ['owner' => $user],
            ['created_at' => 'DESC']
        );

        return $this->render('routine/index.html.twig', [
            'routines' => $routines,
        ]);
    }

    /**
     * Crear nueva rutina
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        RoutineService $routineService,
        ValidatorInterface $validator
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $constraints = new Assert\Collection([
                'name' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('string')]),
                'objective' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('string')]),
                'days_week' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Range(min: 1, max: 7)]),
                'dispo_material' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('string')]),
            ]);
            $constraints->allowExtraFields = true;

            $violations = $validator->validate($data, $constraints);
            if (count($violations) > 0) {
                $this->addFlash('error', 'Los datos de la rutina no son válidos. Por favor, revisa los campos.');
                return $this->redirectToRoute('routine_new');
            }

            try {
                $routine = $routineService->createRoutine($user, [
                    'name' => $data['name'],
                    'objective' => $data['objective'],
                    'days_week' => $data['days_week'],
                    'dispo_material' => $data['dispo_material'],
                ]);

                $this->addFlash('success', '¡Rutina creada exitosamente!');
                return $this->redirectToRoute('routine_edit', ['id' => $routine->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error al crear la rutina: ' . $e->getMessage());
            }
        }

        return $this->render('routine/new.html.twig');
    }

    /**
     * Editar rutina
     */
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Routine $routine,
        Request $request,
        ExerciseRepository $exerciseRepository,
        RoutineService $routineService
    ): Response {
        if ($routine->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Obtener ejercicios disponibles
        $allExercises = $exerciseRepository->findAll();
        $routineExerciseIds = array_map(fn($e) => $e->getId(), $routine->getExercises()->toArray());

        // Agregar ejercicio
        if ($request->isMethod('POST') && $request->request->get('action') === 'add_exercise') {
            if (!$this->isCsrfTokenValid('edit_routine_' . $routine->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de seguridad inválido al añadir ejercicio.');
                return $this->redirectToRoute('routine_edit', ['id' => $routine->getId()]);
            }

            $exerciseId = $request->request->get('exercise_id');
            $exercise = $exerciseRepository->find($exerciseId);
            
            if ($exercise) {
                $routineService->addExerciseToRoutine($routine, $exercise);
                return $this->redirectToRoute('routine_edit', ['id' => $routine->getId()]);
            }
        }

        // Remover ejercicio
        if ($request->isMethod('POST') && $request->request->get('action') === 'remove_exercise') {
            if (!$this->isCsrfTokenValid('edit_routine_' . $routine->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de seguridad inválido al remover ejercicio.');
                return $this->redirectToRoute('routine_edit', ['id' => $routine->getId()]);
            }

            $exerciseId = $request->request->get('exercise_id');
            $exercise = $exerciseRepository->find($exerciseId);
            
            if ($exercise) {
                $routineService->removeExerciseFromRoutine($routine, $exercise);
                return $this->redirectToRoute('routine_edit', ['id' => $routine->getId()]);
            }
        }

        $availableExercises = array_values(array_filter(
            $allExercises,
            fn($e) => !in_array($e->getId(), $routineExerciseIds)
        ));

        $availableExercisesJson = array_map(fn($e) => [
            'id'            => $e->getId(),
            'name'          => $e->getName(),
            'muscularGroup' => $e->getMuscularGroup(),
            'difficulty'    => $e->getDifficulty() ?? '—',
        ], $availableExercises);

        return $this->render('routine/edit.html.twig', [
            'routine'               => $routine,
            'exercises'             => $routine->getExercises(),
            'availableExercises'    => $availableExercises,
            'availableExercisesJson'=> $availableExercisesJson,
        ]);
    }

    /**
     * Iniciar sesión de entrenamiento
     */
    private const DAY_NAMES = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
        4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    #[Route('/{id}/start', name: 'start', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function start(
        Routine $routine,
        TrainingRepository $trainingRepository,
        Request $request
    ): Response {
        if ($routine->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $exerciseTrainings = $routine->getExerciseTrainings();

        // Rutina sin programación por días → mostrar todos los ejercicios
        if ($exerciseTrainings->isEmpty()) {
            $exercises = $routine->getExercises()->toArray();
            return $this->render('routine/start.html.twig', [
                'routine'     => $routine,
                'exercises'   => $exercises,
                'lastSession' => $this->buildLastSession($exercises, $user, $trainingRepository),
                'selectedDay' => null,
                'objectives'  => [],
            ]);
        }

        // Agrupar días disponibles y contar ejercicios por día
        $availableDays = [];
        foreach ($exerciseTrainings as $et) {
            $d = $et->getDayWeek();
            $availableDays[$d] = ($availableDays[$d] ?? 0) + 1;
        }
        ksort($availableDays);

        $day = $request->query->getInt('day', 0);

        // Sin día seleccionado o día inválido → mostrar selector
        if (!$day || !isset($availableDays[$day])) {
            return $this->render('routine/start_day_picker.html.twig', [
                'routine'       => $routine,
                'availableDays' => array_keys($availableDays),
                'dayCounts'     => $availableDays,
                'dayNames'      => self::DAY_NAMES,
                'todayDow'      => (int) date('N'),
            ]);
        }

        // Filtrar y ordenar ejercicios del día seleccionado
        $dayETs = array_values(array_filter(
            $exerciseTrainings->toArray(),
            fn($et) => $et->getDayWeek() === $day
        ));
        usort($dayETs, fn($a, $b) => $a->getOrderRutine() - $b->getOrderRutine());

        $exercises = array_map(fn($et) => $et->getExercise(), $dayETs);

        $objectives = [];
        foreach ($dayETs as $et) {
            $objectives[$et->getExercise()->getId()] = [
                'series'  => $et->getSeriesObjective(),
                'repsMin' => $et->getRepsMin(),
                'repsMax' => $et->getRepsMax(),
                'rest'    => $et->getRestSeconds(),
            ];
        }

        return $this->render('routine/start.html.twig', [
            'routine'     => $routine,
            'exercises'   => $exercises,
            'lastSession' => $this->buildLastSession($exercises, $user, $trainingRepository),
            'selectedDay' => $day,
            'dayName'     => self::DAY_NAMES[$day] ?? "Día $day",
            'objectives'  => $objectives,
        ]);
    }

    private function buildLastSession(array $exercises, $user, TrainingRepository $trainingRepository): array
    {
        $lastSession = [];
        foreach ($exercises as $exercise) {
            $last = $trainingRepository->findLastByUserAndExercise($user, $exercise);
            if ($last) {
                $lastSession[$exercise->getId()] = [
                    'series'     => $last->getCompletedSeries(),
                    'reps'       => $last->getRepetitions(),
                    'weight'     => $last->getWeight(),
                    'date'       => $last->getDate()->format('d/m/Y'),
                    'seriesData' => $last->getSeriesData(),
                ];
            }
        }
        return $lastSession;
    }

    /**
     * Guardar sesión de entrenamiento completada
     */
    #[Route('/{id}/complete', name: 'complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function complete(
        Routine $routine,
        Request $request,
        RoutineService $routineService,
        AchievementService $achievementService,
        EntityManagerInterface $entityManager
    ): Response {
        if ($routine->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $payload = json_decode($request->getContent(), true);
        $exerciseData = is_array($payload) ? ($payload['exercises'] ?? []) : [];
        
        // Registrar entrenamientos
        $completedTrainings = $routineService->completeRoutineSession($routine, $user, $exerciseData);

        // Actualizar usuario (incrementar XP)
        $xpGained = count($completedTrainings) * 10;
        $user->setPointsXp($user->getPointsXp() + $xpGained);
        
        // Verificar nivel
        if ($user->getPointsXp() >= ($user->getLevel() + 1) * 100) {
            $user->setLevel($user->getLevel() + 1);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        // Verificar y desbloquear logros correspondientes (primer entrenamiento, rachas, etc.)
        $achievementService->checkWorkoutAchievements($user);
        $achievementService->checkStreakAchievements($user);

        $this->addFlash('success', "¡Entrenamiento completado! +{$xpGained} XP");

        if ($request->headers->get('Content-Type') === 'application/json' || $request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => "¡Entrenamiento completado! +{$xpGained} XP",
                'redirect' => $this->generateUrl('routine_index'),
            ]);
        }

        return $this->redirectToRoute('routine_index');
    }

    /**
     * Ver progreso de un ejercicio
     */
    #[Route('/exercise/{id}/progress', name: 'exercise_progress', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function exerciseProgress(
        Exercise $exercise,
        RoutineService $routineService
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Historial completo ordenado del más reciente al más antiguo
        $allTrainings = $this->entityManager->createQuery(
            'SELECT t FROM App\Entity\Training t
             WHERE t.appUser = :user AND t.exercise = :exercise
             ORDER BY t.date DESC'
        )
        ->setParameter('user', $user)
        ->setParameter('exercise', $exercise)
        ->getResult();

        $totalTrainings = count($allTrainings);
        $lastTraining   = $allTrainings[0] ?? null;

        // Stats significativas
        $lastWeight  = $lastTraining ? $lastTraining->getWeight() : 0;
        $lastSeries  = $lastTraining ? $lastTraining->getCompletedSeries() : 0;
        $lastReps    = $lastTraining ? $lastTraining->getRepetitions() : 0;
        $weights     = array_filter(array_map(fn($t) => $t->getWeight(), $allTrainings));
        $prWeight    = !empty($weights) ? max($weights) : 0;
        $oneRMs      = array_filter(array_map(fn($t) => $t->getOneRmEstimated(), $allTrainings));
        $bestOneRM   = !empty($oneRMs) ? max($oneRMs) : 0;

        // Progreso: diferencia de peso entre primera y última sesión
        $firstWeight  = !empty($allTrainings) ? end($allTrainings)->getWeight() : 0;
        $weightGain   = $lastWeight - $firstWeight;

        // Últimas 20 sesiones en orden cronológico para el gráfico
        $chartTrainings = array_reverse(array_slice($allTrainings, 0, 20));
        $chartData = ['labels' => [], 'weights' => [], 'oneRMs' => []];
        foreach ($chartTrainings as $t) {
            $chartData['labels'][]  = $t->getDate()->format('d/m');
            $chartData['weights'][] = $t->getWeight();
            $chartData['oneRMs'][]  = $t->getOneRmEstimated() ?? 0;
        }

        $nextLoad = $lastTraining ? $routineService->getNextLoadRecommendation($lastTraining) : 0;

        return $this->render('routine/exercise_progress.html.twig', [
            'exercise'      => $exercise,
            'trainings'     => array_slice($allTrainings, 0, 15),
            'totalTrainings'=> $totalTrainings,
            'lastTraining'  => $lastTraining,
            'lastWeight'    => $lastWeight,
            'lastSeries'    => $lastSeries,
            'lastReps'      => $lastReps,
            'prWeight'      => $prWeight,
            'bestOneRM'     => $bestOneRM,
            'weightGain'    => $weightGain,
            'firstWeight'   => $firstWeight,
            'chartData'     => $chartData,
            'nextLoad'      => $nextLoad,
        ]);
    }

    /**
     * API: Obtener recomendación de carga
     */
    #[Route('/api/exercise/{id}/next-load', name: 'api_next_load', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function apiNextLoad(
        Exercise $exercise,
        RoutineService $routineService,
        TrainingRepository $trainingRepository
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $lastTraining = $trainingRepository->findLastByUserAndExercise($user, $exercise);

        if (!$lastTraining) {
            return $this->json(['exercise_id' => $exercise->getId(), 'next_load' => 0, 'suggestion' => 'Sin historial']);
        }

        $nextLoad = $routineService->getNextLoadRecommendation($lastTraining);

        return $this->json([
            'exercise_id' => $exercise->getId(),
            'next_load'   => $nextLoad,
            'suggestion'  => "{$nextLoad} kg",
        ]);
    }

    /**
     * Eliminar rutina
     */
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Routine $routine,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($routine->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_routine_' . $routine->getId(), $request->request->get('_token'))) {
            $entityManager->remove($routine);
            $entityManager->flush();
            $this->addFlash('success', 'Rutina eliminada');
        } else {
            $this->addFlash('error', 'Token de seguridad inválido.');
        }

        return $this->redirectToRoute('routine_index');
    }
}
