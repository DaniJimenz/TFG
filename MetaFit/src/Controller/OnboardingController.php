<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

final class OnboardingController extends AbstractController
{
    #[Route('/onboarding', name: 'app_onboarding')]
    public function index(): Response
    {
        return $this->render('onboarding/index.html.twig', [
            'controller_name' => 'OnboardingController',
        ]);
    }
    #[Route('/onboarding', name: 'app_onboarding')]
    public function onboarding(Request $request, EntityManagerInterface $entityManager): Response
    {
        // 1. Obtenemos al usuario que acaba de loguearse //
        $user = $this->getUser();

        // 2. Si no hay usuario (acceso directo por URL), lo mandamos al login //
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 3. Si el formulario ha sido enviado (POST) //
        if ($request->isMethod('POST')) {
            // Recogemos los datos del formulario (los nombres deben coincidir con el <input name="...">) //
            $user->setAge((int)$request->request->get('age'));
            $user->setHeight((float)$request->request->get('height'));
            $user->setActualWeight((float)$request->request->get('weight'));
            $user->setGender($request->request->get('gender'));
            $user->setActivityLevel($request->request->get('activity_level'));
            $user->setPurpose($request->request->get('purpose'));

            // 4. Guardamos los cambios en la base de datos //
            $entityManager->flush();

            // 5. ¡Misión cumplida! Ahora sí, vamos a la Home //
            return $this->redirectToRoute('app_home');
        }

        // 6. Si es la primera vez que entra (GET), mostramos la plantilla //
        return $this->render('onboarding/index.html.twig');
    }
}
