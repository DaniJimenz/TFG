<?php

namespace App\Command;

use App\Entity\Training;
use App\Entity\Exercise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-trainings',
    description: 'Crea entrenamientos de prueba para demostrar seguimiento'
)]
class CreateTestTrainingsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Obtener usuario admin
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@metafit.com']);
        if (!$user) {
            $io->error('Usuario admin no encontrado');
            return Command::FAILURE;
        }

        // Obtener primer ejercicio
        $exercise = $this->entityManager->getRepository(Exercise::class)->findOneBy([]);
        if (!$exercise) {
            $io->error('Ejercicio no encontrado');
            return Command::FAILURE;
        }

        // Obtener rutina
        $routine = $this->entityManager->getRepository(\App\Entity\Routine::class)->findOneBy(['owner' => $user]);
        if (!$routine) {
            $io->error('Rutina no encontrada. Ejecuta app:create-test-routine primero');
            return Command::FAILURE;
        }

        // Crear 10 entrenamientos con progresión de peso
        $trainingsCreated = 0;
        $baseWeight = 20;

        for ($i = 0; $i < 10; $i++) {
            $training = new Training();
            $training->setAppUser($user);
            $training->setExercise($exercise);
            $training->setRoutine($routine);
            $training->setDate(new \DateTimeImmutable("-" . (10 - $i) . " days"));
            $training->setCompletedSeries(3);
            $training->setRepetitions(10 - ($i % 3)); // Variar repeticiones
            $training->setWeight($baseWeight + ($i * 2.5)); // Aumentar peso gradualmente
            $training->setDurationMinutes(5 + rand(0, 5));
            $training->setNotes($i === 9 ? 'Excelente sesión!' : null);
            $training->setCompleted(true);

            // Calcular 1RM estimado (Brzycki Formula)
            $reps = $training->getRepetitions();
            $weight = $training->getWeight();
            if ($reps > 0 && $reps < 37) {
                $oneRm = $weight * (36 / (37 - $reps));
                $training->setOneRmEstimated($oneRm);
            }

            $this->entityManager->persist($training);
            $trainingsCreated++;
        }

        $this->entityManager->flush();

        $io->success("Se crearon {$trainingsCreated} entrenamientos de prueba");
        $io->writeln('Ejercicio: ' . $exercise->getName());
        $io->writeln('Rutina: ' . $routine->getName());
        $io->writeln('Progresión de peso: ' . $baseWeight . ' kg → ' . ($baseWeight + 22.5) . ' kg');

        return Command::SUCCESS;
    }
}
