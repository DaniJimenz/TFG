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
                'description' => 'Completa tu primer entrenamiento en MetaFit',
                'type' => 'workout',
                'xp' => 50,
                'icon' => 'fitness_center',
                'judgment' => 'first_workout',
                'sort_order' => 1,
            ],
            [
                'name' => 'Consistencia 7 Días',
                'description' => 'Entrena 7 días consecutivos sin faltar',
                'type' => 'streak',
                'xp' => 150,
                'icon' => 'local_fire_department',
                'judgment' => 'streak_7_days',
                'sort_order' => 2,
            ],
            [
                'name' => 'Hito de 30 Entrenamientos',
                'description' => 'Completa 30 entrenamientos en un mes',
                'type' => 'milestone',
                'xp' => 300,
                'icon' => 'emoji_events',
                'judgment' => 'milestone_30_workouts',
                'sort_order' => 3,
            ],
            [
                'name' => 'Nutrición Consistente',
                'description' => 'Registra tus comidas durante una semana completa',
                'type' => 'nutrition',
                'xp' => 75,
                'icon' => 'restaurant',
                'judgment' => 'nutrition_week',
                'sort_order' => 4,
            ],
            [
                'name' => 'Especialista en Ejercicios',
                'description' => 'Completa ejercicios de todas las categorías de músculos',
                'type' => 'workout',
                'xp' => 200,
                'icon' => 'explore',
                'judgment' => 'all_muscle_groups',
                'sort_order' => 5,
            ],
            [
                'name' => 'Maestro XP',
                'description' => 'Acumula 1.000 puntos de experiencia',
                'type' => 'milestone',
                'xp' => 250,
                'icon' => 'military_tech',
                'judgment' => 'xp_1000',
                'sort_order' => 6,
            ],
            [
                'name' => 'Nivel 5',
                'description' => 'Alcanza el nivel 5 en la plataforma',
                'type' => 'milestone',
                'xp' => 100,
                'icon' => 'trending_up',
                'judgment' => 'level_5',
                'sort_order' => 7,
            ],
            [
                'name' => 'Experto en Pecho',
                'description' => 'Completa 10 ejercicios de pecho diferentes',
                'type' => 'workout',
                'xp' => 120,
                'icon' => 'favorite',
                'judgment' => 'chest_expert',
                'sort_order' => 8,
            ],
            [
                'name' => 'Espalda Fortalecida',
                'description' => 'Completa 10 ejercicios de espalda diferentes',
                'type' => 'workout',
                'xp' => 120,
                'icon' => 'back_hand',
                'judgment' => 'back_expert',
                'sort_order' => 9,
            ],
            [
                'name' => 'Piernas Potentes',
                'description' => 'Completa 10 ejercicios de piernas diferentes',
                'type' => 'workout',
                'xp' => 120,
                'icon' => 'directions_run',
                'judgment' => 'legs_expert',
                'sort_order' => 10,
            ],
            [
                'name' => 'Core Resistente',
                'description' => 'Completa 5 ejercicios de core',
                'type' => 'workout',
                'xp' => 100,
                'icon' => 'center_focus_strong',
                'judgment' => 'core_expert',
                'sort_order' => 11,
            ],
            [
                'name' => 'Red de Atletas',
                'description' => 'Invita a un amigo a usar MetaFit',
                'type' => 'milestone',
                'xp' => 50,
                'icon' => 'group_add',
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
                $io->writeln("   {$data['name']} ya existe, omitiendo...");
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

        $io->success("Se crearon {$count} logros nuevos exitosamente");

        return Command::SUCCESS;
    }
}
