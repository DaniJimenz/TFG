<?php

namespace App\Command;

use App\Entity\Exercise;
use App\Service\PexelsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-exercises', description: 'Ejercicios desde JSON con imágenes y videos de Pexels')]
class ImportExercisesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private PexelsService $pexelsService;

    public function __construct(EntityManagerInterface $entityManager, PexelsService $pexelsService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->pexelsService = $pexelsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jsonPath = 'ejercicios.json'; // Nombre del archivo en la raíz

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

        foreach ($data as $item) {
            // Verificar si el ejercicio ya existe
            $existingExercise = $this->entityManager->getRepository(Exercise::class)
                ->findOneBy(['name' => $item['name']]);
            
            if ($existingExercise) {
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

            // SIEMPRE obtener imagen de Pexels (ignorar JSON)
            $imageUrl = $this->pexelsService->searchImage($item['name']);
            if (!$imageUrl) {
                // Fallback: usar búsqueda genérica
                $imageUrl = $this->pexelsService->searchImage('gym workout fitness');
            }
            $exercise->setUrlImage($imageUrl);

            // SIEMPRE obtener video de Pexels (ignorar JSON)
            $videoUrl = $this->pexelsService->searchVideo($item['name']);
            if (!$videoUrl) {
                // Fallback: usar búsqueda genérica
                $videoUrl = $this->pexelsService->searchVideo('fitness exercise workout');
            }
            $exercise->setUrlVideo($videoUrl);

            $this->entityManager->persist($exercise);
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();

        $io->newLine();
        $io->success('¡Se han importado ' . count($data) . ' ejercicios correctamente con imágenes y videos!');

        return Command::SUCCESS;
    }
}
