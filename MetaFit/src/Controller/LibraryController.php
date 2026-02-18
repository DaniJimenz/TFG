<?php

// src/Controller/LibraryController.php
namespace App\Controller;

use App\Repository\ExerciseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LibraryController extends AbstractController
{
    #[Route('/library', name: 'app_library_index')]
    public function index(ExerciseRepository $exerciseRepository): Response
    {
        // Traemos todos los ejercicios de la BD
        $exercises = $exerciseRepository->findAll();

        return $this->render('library/index.html.twig', [
            'exercises' => $exercises,
        ]);
    }
}
