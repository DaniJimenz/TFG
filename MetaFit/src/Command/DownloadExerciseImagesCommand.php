<?php

namespace App\Command;

use App\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:download-exercise-images',
    description: 'Descarga imágenes del ejercicios.json y las guarda localmente',
)]
class DownloadExerciseImagesCommand extends Command
{
    private const UPLOAD_DIR = 'public/uploads/exercises';
    private const JSON_FILE = 'ejercicios.json';

    public function __construct(
        private ExerciseRepository $exerciseRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Descargando imágenes de ejercicios...');

        // Crear directorio si no existe
        $uploadDir = $this->projectDir . '/' . self::UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            $io->info("Directorio creado: $uploadDir");
        }

        // Leer el JSON
        $jsonFile = $this->projectDir . '/' . self::JSON_FILE;
        if (!file_exists($jsonFile)) {
            $io->error("Archivo no encontrado: $jsonFile");
            return Command::FAILURE;
        }

        $exercises = json_decode(file_get_contents($jsonFile), true);
        if (!is_array($exercises)) {
            $io->error("JSON inválido");
            return Command::FAILURE;
        }

        $downloadedImages = [];
        $failedDownloads = [];

        $progressBar = $io->createProgressBar(count($exercises));
        $progressBar->start();

        foreach ($exercises as $exerciseData) {
            $progressBar->advance();

            if (!isset($exerciseData['url_image'])) {
                continue;
            }

            $imageUrl = $exerciseData['url_image'];

            // Si ya descargamos esta imagen, saltarla
            if (isset($downloadedImages[$imageUrl])) {
                continue;
            }

            // Descargar imagen
            $filename = $this->downloadImage($imageUrl, $uploadDir);

            if ($filename === false) {
                $failedDownloads[] = $imageUrl;
                continue;
            }

            $downloadedImages[$imageUrl] = $filename;
        }

        $progressBar->finish();
        $io->newLine();

        // Actualizar base de datos
        $io->section('Actualizando base de datos...');
        $exercises = $this->exerciseRepository->findAll();

        foreach ($exercises as $exercise) {
            $urlImage = $exercise->getUrlImage();

            if ($urlImage && isset($downloadedImages[$urlImage])) {
                $localPath = '/uploads/exercises/' . $downloadedImages[$urlImage];
                $exercise->setUrlImage($localPath);
            }
        }

        $this->entityManager->flush();

        $io->success([
            "¡Proceso completado!",
            "Imágenes descargadas: " . count($downloadedImages),
            "Fallos: " . count($failedDownloads),
        ]);

        if (!empty($failedDownloads)) {
            $io->warning("Imágenes que no pudieron descargarse:");
            foreach ($failedDownloads as $url) {
                $io->writeln("- $url");
            }
        }

        return Command::SUCCESS;
    }

    private function downloadImage(string $url, string $uploadDir): string|false
    {
        try {
            $imageContent = @file_get_contents($url);

            if ($imageContent === false) {
                return false;
            }

            // Generar nombre único basado en la URL
            $urlHash = hash('sha256', $url);
            $extension = $this->getExtensionFromUrl($url);
            $filename = 'exercise-' . substr($urlHash, 0, 12) . '.' . $extension;

            $filepath = $uploadDir . '/' . $filename;

            // No descargar si ya existe
            if (file_exists($filepath)) {
                return $filename;
            }

            // Guardar archivo
            if (file_put_contents($filepath, $imageContent) === false) {
                return false;
            }

            return $filename;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getExtensionFromUrl(string $url): string
    {
        // Obtener la extensión de la URL
        $urlPath = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($urlPath, PATHINFO_EXTENSION);

        // Si no hay extensión, inferir del content-type
        if (empty($extension)) {
            $headers = @get_headers($url, 1);
            if ($headers && isset($headers['Content-Type'])) {
                $contentType = $headers['Content-Type'];
                if (str_contains($contentType, 'image/jpeg') || str_contains($contentType, 'image/jpg')) {
                    return 'jpg';
                } elseif (str_contains($contentType, 'image/png')) {
                    return 'png';
                } elseif (str_contains($contentType, 'image/webp')) {
                    return 'webp';
                }
            }
            return 'jpg'; // Default
        }

        return strtolower($extension);
    }
}
