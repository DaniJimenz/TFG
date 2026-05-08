<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TrainingPreference;
use App\Repository\TrainingPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/profile', name: 'profile_')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    /**
     * Ver perfil del usuario
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        TrainingPreferenceRepository $prefRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $preferences = $prefRepo->findOneBy(['user' => $user]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Editar información personal
     */
    #[Route('/edit-personal', name: 'edit_personal', methods: ['GET', 'POST'])]
    public function editPersonal(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $constraints = new Assert\Collection([
                'name' => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('string')]),
                'lastname' => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('string')]),
                'age' => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Range(['min' => 14, 'max' => 100])]),
                'height' => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Range(['min' => 100, 'max' => 250])]),
                'gender' => new Assert\Optional([new Assert\Choice(['H', 'M', 'Hombre', 'Mujer'])]),
                'actual_weight' => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Range(['min' => 30, 'max' => 300])]),
                'purpose' => new Assert\Optional([new Assert\Type('string')]),
                'activity_level' => new Assert\Optional([new Assert\Type('string')]),
            ]);
            $constraints->allowExtraFields = true;
            $constraints->allowMissingFields = true;

            $violations = $validator->validate($data, $constraints);
            if (count($violations) > 0) {
                $this->addFlash('error', 'Los datos introducidos no son válidos. Por favor, revisa tus medidas.');
                return $this->redirectToRoute('profile_edit_personal');
            }

            $user->setName($data['name']);
            $user->setLastname($data['lastname']);
            $user->setAge(isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : $user->getAge());
            $user->setHeight(isset($data['height']) && $data['height'] !== '' ? (float)$data['height'] : $user->getHeight());
            $user->setGender(!empty($data['gender']) ? $data['gender'] : $user->getGender());
            $user->setActualWeight(isset($data['actual_weight']) && $data['actual_weight'] !== '' ? (float)$data['actual_weight'] : $user->getActualWeight());
            $user->setPurpose($data['purpose'] ?? null);
            $user->setActivityLevel($data['activity_level'] ?? null);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', '¡Perfil actualizado exitosamente!');
            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/edit_personal.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Gestionar preferencias de entrenamiento
     */
    #[Route('/preferences', name: 'preferences', methods: ['GET', 'POST'])]
    public function preferences(
        Request $request,
        TrainingPreferenceRepository $prefRepo,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $preferences = $prefRepo->findOneBy(['user' => $user]);
        if (!$preferences) {
            $preferences = new TrainingPreference();
            $preferences->setUser($user);
            $entityManager->persist($preferences);
            $entityManager->flush();
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $constraints = new Assert\Collection([
                'preferred_time' => new Assert\Optional([new Assert\Type('string')]),
                'training_intensity' => new Assert\Optional([new Assert\Type('string')]),
                'training_duration_minutes' => new Assert\Optional([new Assert\Type('numeric'), new Assert\Positive()]),
                'rest_between_sets_seconds' => new Assert\Optional([new Assert\Type('numeric'), new Assert\Positive()]),
                'reminder_minutes_before' => new Assert\Optional([new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
                'measurement_unit' => new Assert\Optional([new Assert\Choice(['kg', 'lbs'])]),
            ]);
            $constraints->allowExtraFields = true;
            $constraints->allowMissingFields = true;

            $violations = $validator->validate($data, $constraints);
            if (count($violations) > 0) {
                $this->addFlash('error', 'Los datos de preferencias no son válidos. Revisa los valores introducidos.');
                return $this->redirectToRoute('profile_preferences');
            }

            $preferences->setPreferredTime($data['preferred_time']);
            $preferences->setTrainingIntensity($data['training_intensity']);
            $preferences->setTrainingDurationMinutes((int)($data['training_duration_minutes'] ?? 60));
            $preferences->setRestBetweenSetsSeconds((int)($data['rest_between_sets_seconds'] ?? 60));
            $preferences->setNotificationsEnabled(isset($data['notifications_enabled']));
            $preferences->setReminderBeforeTraining(isset($data['reminder_before_training']));
            $preferences->setReminderMinutesBefore((int)($data['reminder_minutes_before'] ?? 30));
            $preferences->setSoundEnabled(isset($data['sound_enabled']));
            $preferences->setMeasurementUnit($data['measurement_unit'] ?? 'kg');
            $preferences->setProfilePublic(isset($data['profile_public']));
            $preferences->setStatsVisible(isset($data['stats_visible']));
            $preferences->setAchievementsVisible(isset($data['achievements_visible']));
            $preferences->setRoutinesVisible(isset($data['routines_visible']));
            $preferences->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($preferences);
            $entityManager->flush();

            $this->addFlash('success', '¡Preferencias actualizadas!');
            return $this->redirectToRoute('profile_preferences');
        }

        return $this->render('profile/preferences.html.twig', [
            'preferences' => $preferences,
        ]);
    }

    /**
     * Cambiar contraseña
     */
    #[Route('/change-password', name: 'change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            if (!$this->isCsrfTokenValid('change_password', $data['_token'] ?? '')) {
                $this->addFlash('error', 'Token de seguridad inválido. Inténtalo de nuevo.');
                return $this->redirectToRoute('profile_change_password');
            }

            if (!$passwordHasher->isPasswordValid($user, $data['current_password'] ?? '')) {
                $this->addFlash('error', 'Contraseña actual incorrecta');
                return $this->redirectToRoute('profile_change_password');
            }

            if (($data['new_password'] ?? '') !== ($data['confirm_password'] ?? '')) {
                $this->addFlash('error', 'Las contraseñas nuevas no coinciden');
                return $this->redirectToRoute('profile_change_password');
            }

            if (strlen($data['new_password']) < 8) {
                $this->addFlash('error', 'La contraseña debe tener al menos 8 caracteres');
                return $this->redirectToRoute('profile_change_password');
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $data['new_password']);
            $user->setPassword($hashedPassword);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', '¡Contraseña cambiada exitosamente!');
            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/change_password.html.twig');
    }
}
