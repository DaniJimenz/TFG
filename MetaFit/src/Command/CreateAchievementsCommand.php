<?php

namespace App\Command;

use App\Entity\Achievement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:create-achievements', description: 'Crea los logros iniciales de la app')]
class CreateAchievementsCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $achievements = [
            [
                'name' => 'Primer Entrenamiento',
                'description' => 'Completa tu primer entrenamiento en MetaFit AI',
                'type' => 'workout',
                'xp' => 50,
                'icon' => '💪',
                'judgment' => 'first_workout',
                'sort_order' => 1,
            ],
            [
                'name' => 'Guerrero de 7 Días',
                'description' => 'Entrena 7 días consecutivos sin faltar',
                'type' => 'streak',
                'xp' => 150,
                'icon' => '🔥',
                'judgment' => 'streak_7_days',
                'sort_order' => 2,
            ],
            [
                'name' => 'Monstruo de 30 Días',
                'description' => 'Completa 30 entrenamientos en un mes',
                'type' => 'milestone',
                'xp' => 300,
                'icon' => '🏆',
                'judgment' => 'milestone_30_workouts',
                'sort_order' => 3,
            ],
            [
                'name' => 'Nutricionista Amateur',
                'description' => 'Registra tus comidas durante una semana completa',
                'type' => 'nutrition',
                'xp' => 75,
                'icon' => '🥗',
                'judgment' => 'nutrition_week',
                'sort_order' => 4,
            ],
            [
                'name' => 'Explorador de Ejercicios',
                'description' => 'Completa ejercicios de todas las categorías de músculos',
                'type' => 'workout',
                'xp' => 200,
                'icon' => '🧭',
                'judgment' => 'all_muscle_groups',
                'sort_order' => 5,
            ],
            [
                'name' => 'Miltonario',
                'description' => 'Acumula 1.000 puntos XP',
                'type' => 'milestone',
                'xp' => 250,
                'icon' => '🏅',
                'judgment' => 'xp_1000',
                'sort_order' => 6,
            ],
            [
                'name' => 'Nivel 5',
                'description' => 'Alcanza el nivel 5 en la app',
                'type' => 'milestone',
                'xp' => 100,
                'icon' => '📈',
                'judgment' => 'level_5',
                'sort_order' => 7,
            ],
            [
                'name' => 'Maestro de Pecho',
                'description' => 'Completa 10 ejercicios de pecho diferentes',
                'type' => 'workout',
                'xp' => 120,
                'icon' => '💯',
                'judgment' => 'chest_expert',
                'sort_order' => 8,
            ],
            [
                'name' => 'Espaldas Fuertes',
                'description' => 'Completa 10 ejercicios de espalda diferentes',
                'type' => 'workout',
                'xp' => 120,
                'icon' => '🔙',
                'judgment' => 'back_expert',
                'sort_order' => 9,
            ],
            [
                'name' => 'Piernas de Acero',
                'description' => 'Completa 10 ejercicios de piernas diferentes',
                'type' => 'workout',
                'xp' => 120,
                'icon' => '🦵',
                'judgment' => 'legs_expert',
                'sort_order' => 10,
            ],
            [
                'name' => 'Núcleo Fortalecido',
                'description' => 'Completa 5 ejercicios de core',
                'type' => 'workout',
                'xp' => 100,
                'icon' => '🎯',
                'judgment' => 'core_expert',
                'sort_order' => 11,
            ],
            [
                'name' => 'Sociólogo del Fitness',
                'description' => 'Invita a un amigo a usar MetaFit AI',
                'type' => 'milestone',
                'xp' => 50,
                'icon' => '👥',
                'judgment' => 'invite_friend',
                'sort_order' => 12,
            ],
        ];

        $count = 0;
        foreach ($achievements as $data) {
            // Verificar si ya existe
            $existing = $this->entityManager->getRepository(Achievement::class)->findOneBy([
                'judgment' => $data['judgment']
            ]);

            if ($existing) {
                $io->writeln("⏭️  {$data['name']} ya existe, omitiendo...");
                continue;
            }

            $achievement = new Achievement();
            $achievement->setName($data['name']);
            $achievement->setDescription($data['description']);
            $achievement->setType($data['type']);
            $achievement->setOtorgatedXp($data['xp']);
            $achievement->setUrlIcon($data['icon']);
            $achievement->setJudgment($data['judgment']);
            $achievement->setSortOrder($data['sort_order']);

            $this->entityManager->persist($achievement);
            $count++;
        }

        $this->entityManager->flush();

        $io->success("✓ Se crearon {$count} logros nuevos exitosamente!");

        return Command::SUCCESS;
    }
}
