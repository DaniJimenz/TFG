<?php

namespace App\Command;

use App\Entity\Exercise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:import-exercises',
    description: 'Importa ejercicios desde ExerciseDB JSON'
)]
class ImportExercisesFromExerciseDBCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Ruta del archivo JSON
        $jsonPath = $this->getApplication()->getKernel()->getProjectDir() . '/exercises.json';

        if (!file_exists($jsonPath)) {
            $io->error("Archivo no encontrado: $jsonPath");
            $io->info('Descarga exercises.json desde: https://raw.githubusercontent.com/yuhonas/free-exercise-db/main/exercises.json');
            return Command::FAILURE;
        }

        try {
            // Leer el archivo JSON
            $jsonContent = file_get_contents($jsonPath);
            
            // Decodificar JSON
            $exercisesData = json_decode($jsonContent, true);

            if (!is_array($exercisesData)) {
                $io->error('El formato del JSON no es válido');
                return Command::FAILURE;
            }

            $io->info("Se encontraron " . count($exercisesData) . " ejercicios para importar");
            
            // Crear barra de progreso
            $progressBar = $io->createProgressBar(count($exercisesData));
            $progressBar->start();

            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($exercisesData as $data) {
                // Validar datos requeridos
                if (empty($data['name'])) {
                    $progressBar->advance();
                    $skipped++;
                    continue;
                }

                // Buscar si ya existe
                $existingExercise = $this->entityManager->getRepository(Exercise::class)
                    ->findOneBy(['name' => trim($data['name'])]);

                if ($existingExercise) {
                    // Actualizar
                    $existingExercise->setUrlImage($data['gifUrl'] ?? null);
                    $existingExercise->setMuscularGroup($data['target'] ?? 'Core');
                    $existingExercise->setDescription($data['equipment'] ?? '');
                    $existingExercise->setTechnique($data['bodyPart'] ?? '');
                    
                    $this->entityManager->flush();
                    $updated++;
                } else {
                    // Crear nuevo
                    $exercise = new Exercise();
                    $exercise->setName(trim($data['name']));
                    $exercise->setMuscularGroup($data['target'] ?? 'Core');
                    $exercise->setDescription($data['equipment'] ?? '');
                    $exercise->setTechnique($data['bodyPart'] ?? '');
                    $exercise->setUrlImage($data['gifUrl'] ?? null);
                    $exercise->setUrlVideo(null);
                    $exercise->setDifficulty('Intermedio');
                    $exercise->setCompound(false);

                    $this->entityManager->persist($exercise);
                    $created++;
                }

                $progressBar->advance();
            }

            $this->entityManager->flush();
            $progressBar->finish();

            $io->newLine(2);
            $io->success([
                "¡Importación completada!",
                "Nuevos: $created",
                "Actualizados: $updated",
                "Omitidos: $skipped",
                "Total: " . count($exercisesData)
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Error durante la importación: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
