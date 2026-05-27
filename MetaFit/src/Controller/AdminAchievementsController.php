<?php

namespace App\Controller;

use App\Entity\Achievement;
use App\Form\AchievementType;
use App\Repository\AchievementRepository;
use App\Repository\UserAchievementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/achievements', name: 'admin_achievements_')]
#[IsGranted('ROLE_ADMIN')]
class AdminAchievementsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(AchievementRepository $achievementRepository, UserAchievementRepository $userAchievementRepository): Response
    {
        $achievements = $achievementRepository->findBy([], ['sort_order' => 'ASC']);

        $unlockedCounts = [];
        foreach ($achievements as $achievement) {
            $unlockedCounts[$achievement->getId()] = $userAchievementRepository->count(['achievement' => $achievement]);
        }

        return $this->render('admin/achievements/index.html.twig', [
            'achievements' => $achievements,
            'unlockedCounts' => $unlockedCounts,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AchievementRepository $achievementRepository): Response
    {
        $achievement = new Achievement();

        $maxOrder = $achievementRepository->createQueryBuilder('a')
            ->select('MAX(a.sort_order)')
            ->getQuery()
            ->getSingleScalarResult();
        $achievement->setSortOrder(($maxOrder ?? 0) + 1);

        $form = $this->createForm(AchievementType::class, $achievement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($achievement);
            $entityManager->flush();

            $this->addFlash('success', 'Logro creado correctamente.');
            return $this->redirectToRoute('admin_achievements_index');
        }

        return $this->render('admin/achievements/edit.html.twig', [
            'achievement' => $achievement,
            'form' => $form,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Achievement $achievement, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AchievementType::class, $achievement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Logro actualizado correctamente.');
            return $this->redirectToRoute('admin_achievements_index');
        }

        return $this->render('admin/achievements/edit.html.twig', [
            'achievement' => $achievement,
            'form' => $form,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Achievement $achievement, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_achievement_' . $achievement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($achievement);
            $entityManager->flush();
            $this->addFlash('success', 'Logro eliminado.');
        }

        return $this->redirectToRoute('admin_achievements_index');
    }
}
