<?php

namespace App\Command;

use App\Entity\Routine;
use App\Entity\Exercise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-routine',
    description: 'Crea una rutina de prueba con ejercicios'
)]
class CreateTestRoutineCommand extends Command
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

        // Crear rutina
        $routine = new Routine();
        $routine->setName('Rutina Full Body');
        $routine->setObjective('Ganar Masa');
        $routine->setDaysWeek(3);
        $routine->setDispoMaterial('Gym Completo');
        $routine->setOwner($user);
        $routine->setCreatedAt(new \DateTimeImmutable());
        $routine->setDateStart(new \DateTimeImmutable());
        $routine->setActive(true);

        // Obtener ejercicios
        $exercises = $this->entityManager->getRepository(Exercise::class)
            ->findBy([], [], 5); // Primeros 5 ejercicios

        foreach ($exercises as $exercise) {
            $routine->addExercise($exercise);
        }

        $this->entityManager->persist($routine);
        $this->entityManager->flush();

        $io->success('Rutina de prueba creada exitosamente');
        $io->writeln('Nombre: ' . $routine->getName());
        $io->writeln('Ejercicios: ' . count($exercises));
        $io->writeln('Rutina ID: ' . $routine->getId());

        return Command::SUCCESS;
    }
}
