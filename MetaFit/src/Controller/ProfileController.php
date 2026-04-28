<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TrainingPreference;
use App\Entity\DataBackup;
use App\Repository\TrainingPreferenceRepository;
use App\Repository\SocialConnectionRepository;
use App\Repository\DataBackupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $user->setName($data['name']);
            $user->setLastname($data['lastname']);
            $user->setAge((int)($data['age'] ?? null));
            $user->setHeight((float)($data['height'] ?? null));
            $user->setGender($data['gender'] ?? null);
            $user->setActualWeight((float)($data['actual_weight'] ?? null));
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
        EntityManagerInterface $entityManager
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
     * Gestionar conexiones sociales
     */
    #[Route('/social', name: 'social', methods: ['GET', 'POST'])]
    public function social(
        Request $request,
        SocialConnectionRepository $socialRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $connections = $socialRepo->findBy(['user' => $user]);

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            if ($action === 'update') {
                $connectionId = $request->request->get('connection_id');
                $connection = $entityManager->getRepository(\App\Entity\SocialConnection::class)->find($connectionId);
                
                if ($connection && $connection->getUser() === $user) {
                    $connection->setShareStats(isset($request->request->get('data')['share_stats']));
                    $connection->setAutoPost(isset($request->request->get('data')['auto_post']));
                    $connection->setLastSync(new \DateTimeImmutable());
                    $entityManager->persist($connection);
                    $entityManager->flush();
                    
                    return $this->json(['success' => true]);
                }
            }
            
            if ($action === 'disconnect') {
                $connectionId = $request->request->get('connection_id');
                $connection = $entityManager->getRepository(\App\Entity\SocialConnection::class)->find($connectionId);
                
                if ($connection && $connection->getUser() === $user) {
                    $entityManager->remove($connection);
                    $entityManager->flush();
                    
                    $this->addFlash('success', '¡Conexión eliminada!');
                }
            }
        }

        return $this->render('profile/social.html.twig', [
            'connections' => $connections,
        ]);
    }

    /**
     * Gestionar datos y backups
     */
    #[Route('/backups', name: 'backups', methods: ['GET', 'POST'])]
    public function backups(
        Request $request,
        DataBackupRepository $backupRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $backups = $backupRepo->findBy(['user' => $user], ['created_at' => 'DESC']);

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            if ($action === 'create_backup') {
                $data = $request->request->all();
                
                $backup = new DataBackup();
                $backup->setUser($user);
                $backup->setFileName('metafit_backup_' . $user->getId() . '_' . date('Y-m-d_H-i-s') . '.json');
                $backup->setBackupType($data['backup_type'] ?? 'full');
                $backup->setIncludePersonalData(isset($data['include_personal_data']));
                $backup->setIncludeRoutines(isset($data['include_routines']));
                $backup->setIncludeTrainings(isset($data['include_trainings']));
                $backup->setIncludeMeals(isset($data['include_meals']));
                $backup->setIncludeAchievements(isset($data['include_achievements']));
                $backup->setCreatedAt(new \DateTimeImmutable());
                $backup->setExpiresAt(new \DateTimeImmutable('+30 days'));
                $backup->setIsAutomated(false);
                $backup->setFileSizeBytes(0); // Se calcula al generar

                $entityManager->persist($backup);
                $entityManager->flush();

                $this->addFlash('success', '¡Backup creado exitosamente!');
                return $this->redirectToRoute('profile_backups');
            }
            
            if ($action === 'delete_backup') {
                $backupId = $request->request->get('backup_id');
                $backup = $backupRepo->find($backupId);
                
                if ($backup && $backup->getUser() === $user) {
                    $entityManager->remove($backup);
                    $entityManager->flush();
                    
                    $this->addFlash('success', '¡Backup eliminado!');
                }
            }
        }

        return $this->render('profile/backups.html.twig', [
            'backups' => $backups,
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
            
            if (!$passwordHasher->isPasswordValid($user, $data['current_password'])) {
                $this->addFlash('error', 'Contraseña actual incorrecta');
                return $this->redirectToRoute('profile_change_password');
            }

            if ($data['new_password'] !== $data['confirm_password']) {
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

    /**
     * Privacidad y seguridad
     */
    #[Route('/privacy', name: 'privacy', methods: ['GET', 'POST'])]
    public function privacy(
        Request $request,
        TrainingPreferenceRepository $prefRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $preferences = $prefRepo->findOneBy(['user' => $user]);

        if ($request->isMethod('POST')) {
            if (!$preferences) {
                $preferences = new TrainingPreference();
                $preferences->setUser($user);
            }

            $data = $request->request->all();
            $preferences->setProfilePublic(isset($data['profile_public']));
            $preferences->setStatsVisible(isset($data['stats_visible']));
            $preferences->setAchievementsVisible(isset($data['achievements_visible']));
            $preferences->setRoutinesVisible(isset($data['routines_visible']));
            $preferences->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($preferences);
            $entityManager->flush();

            $this->addFlash('success', '¡Configuración de privacidad actualizada!');
            return $this->redirectToRoute('profile_privacy');
        }

        return $this->render('profile/privacy.html.twig', [
            'preferences' => $preferences,
        ]);
    }
}
