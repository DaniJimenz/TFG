<?php

namespace App\Command;

use App\Entity\Exercise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-exercises', description: 'Ejercicios desde JSON')]
class ImportExercisesCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
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

        foreach ($data as $item) {
            $exercise = new Exercise();
            $exercise->setName($item['name']);
            $exercise->setMuscularGroup($item['muscular_group']);
            $exercise->setTechnique($item['technique']);
            $exercise->setUrlImage($item['url_image']);
            $exercise->setDescription($item['description']);

            $exercise->setCompound(false);
            // Valores por defecto para campos que no estén en el JSON
            $exercise->setDifficulty($item['difficulty'] ?? 'Media');

            $this->entityManager->persist($exercise);
        }

        $this->entityManager->flush();

        $io->success('¡Se han importado ' . count($data) . ' ejercicios correctamente!');

        return Command::SUCCESS;
    }
}
