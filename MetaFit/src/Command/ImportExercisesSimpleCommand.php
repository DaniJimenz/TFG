<?php

namespace App\Command;

use App\Entity\Exercise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-exercises-simple', description: 'Importar ejercicios desde JSON con URLs del propio JSON')]
class ImportExercisesSimpleCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jsonPath = 'ejercicios.json';

        if (!file_exists($jsonPath)) {
            $io->error('No se encuentra el archivo ejercicios.json en la raíz del proyecto.');
            return Command::FAILURE;
        }

        $jsonData = file_get_contents($jsonPath);
        $data = json_decode($jsonData, true);

        if (null === $data) {
            $io->error('El formato del archivo JSON no es válido.');
            return Command::FAILURE;
        }

        $progressBar = $io->createProgressBar(count($data));
        $progressBar->start();

        $imported = 0;
        $updated = 0;
        foreach ($data as $item) {
            // Verificar si el ejercicio ya existe
            $existingExercise = $this->entityManager->getRepository(Exercise::class)
                ->findOneBy(['name' => $item['name']]);
            
            if ($existingExercise) {
                // Si el ejercicio existe, sobrescribimos la URL de la imagen con la del JSON original
                $existingExercise->setUrlImage($item['url_image'] ?? '');
                $existingExercise->setUrlVideo($item['url_video'] ?? '');
                $updated++;
                $progressBar->advance();
                continue;
            }

            $exercise = new Exercise();
            $exercise->setName($item['name']);
            $exercise->setMuscularGroup($item['muscular_group']);
            $exercise->setTechnique($item['technique']);
            $exercise->setDescription($item['description'] ?? '');
            $exercise->setDifficulty($item['difficulty'] ?? 'Intermedio');
            $exercise->setCompound(false);
            $exercise->setNecessaryMaterial($item['necessary_material'] ?? '');
            
            // Usar URLs del JSON
            $exercise->setUrlImage($item['url_image'] ?? '');
            $exercise->setUrlVideo($item['url_video'] ?? '');

            $this->entityManager->persist($exercise);
            $imported++;
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();

        $io->newLine();
        $io->success("¡Se han importado $imported ejercicios y actualizado $updated correctamente!");

        return Command::SUCCESS;
    }
}
