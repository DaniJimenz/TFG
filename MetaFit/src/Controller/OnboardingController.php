<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

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
    public function onboarding(Request $request, EntityManagerInterface $entityManager, RecommendationService $recommendationService, ValidatorInterface $validator): Response
    {
        // 1. Obtenemos al usuario que acaba de loguearse //
        $user = $this->getUser();

        // 2. Si no hay usuario (acceso directo por URL), lo mandamos al login //
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 3. Si el formulario ha sido enviado (POST) //
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            // Validación estricta y segura de los datos de entrada
            $constraints = new Assert\Collection([
                'age' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Range(min: 14, max: 100)]),
                'height' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Range(min: 100, max: 250)]),
                'weight' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Range(min: 30, max: 300)]),
                'gender' => new Assert\Required([new Assert\NotBlank(), new Assert\Choice(choices: ['H', 'M'])]),
                'activity_level' => new Assert\Required([new Assert\NotBlank(), new Assert\Choice(choices: ['Baja', 'Media', 'Alta'])]),
                'purpose' => new Assert\Required([new Assert\NotBlank(), new Assert\Choice(choices: ['Perder grasa', 'Mantenimiento', 'Ganar masa muscular'])]),
            ]);
            $constraints->allowExtraFields = true; // Permite campos extra de sistema sin dar error

            $violations = $validator->validate($data, $constraints);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $field = str_replace(['[', ']'], '', $violation->getPropertyPath());
                    $this->addFlash('error', "Revisa el campo '{$field}': Valores no permitidos.");
                }
                return $this->redirectToRoute('app_onboarding');
            }

            $user->setAge((int)$data['age']);
            $user->setHeight((float)$data['height']);
            $user->setActualWeight((float)$data['weight']);
            $user->setGender($data['gender']);
            $user->setActivityLevel($data['activity_level']);
            $user->setPurpose($data['purpose']);

            // 4. Guardamos los cambios en la base de datos //
            $entityManager->flush();

            // Generamos su primera rutina real guardada en BD //
            $recommendationService->generateInitialRoutine($user);

            // 5. ¡Misión cumplida! Ahora sí, vamos a la Home //
            return $this->redirectToRoute('app_home');
        }

        // 6. Si es la primera vez que entra (GET), mostramos la plantilla //
        return $this->render('onboarding/index.html.twig');
    }
}
