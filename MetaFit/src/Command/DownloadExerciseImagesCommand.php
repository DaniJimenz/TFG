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
        $urlHash = hash('sha256', $url);
        $urlPath = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION)) ?: 'jpg';
        // Strip query string from extension (e.g. "jpeg?foo=bar" → "jpeg")
        $extension = preg_replace('/[^a-z0-9].*/', '', $extension);
        $filename = 'exercise-' . substr($urlHash, 0, 12) . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;

        if (file_exists($filepath)) {
            return $filename;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; MetaFit/1.0)',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $imageContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($imageContent === false || $httpCode !== 200) {
            return false;
        }

        // Corregir extensión si la URL no la tenía
        if (empty(pathinfo($urlPath, PATHINFO_EXTENSION))) {
            if (str_contains($contentType, 'png')) {
                $extension = 'png';
            } elseif (str_contains($contentType, 'webp')) {
                $extension = 'webp';
            } else {
                $extension = 'jpg';
            }
            $filename = 'exercise-' . substr($urlHash, 0, 12) . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;
        }

        if (file_put_contents($filepath, $imageContent) === false) {
            return false;
        }

        return $filename;
    }
}
