<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploadService
{
    private string $uploadsDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $uploadsDirectory, SluggerInterface $slugger)
    {
        $this->uploadsDirectory = $uploadsDirectory;
        $this->slugger = $slugger;
    }

    /**
     * Guarda una imagen subida y retorna la ruta relativa
     */
    public function uploadExerciseImage(UploadedFile $file, ?string $oldImagePath = null): string
    {
        // Eliminar imagen anterior si existe
        if ($oldImagePath && file_exists($this->uploadsDirectory . '/' . $oldImagePath)) {
            unlink($this->uploadsDirectory . '/' . $oldImagePath);
        }

        // Generar nombre único
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Mover archivo
        $file->move($this->uploadsDirectory, $newFilename);

        return $newFilename;
    }

    /**
     * Elimina una imagen
     */
    public function deleteExerciseImage(?string $imagePath): void
    {
        if ($imagePath && file_exists($this->uploadsDirectory . '/' . $imagePath)) {
            unlink($this->uploadsDirectory . '/' . $imagePath);
        }
    }

    /**
     * Obtiene la ruta web de una imagen
     */
    public function getWebPath(string $filename): string
    {
        return '/uploads/exercises/' . $filename;
    }
}
