<?php

namespace App\Command;

use App\Entity\User;
use App\Service\AchievementService;
use App\Repository\AchievementRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:unlock-achievements-demo', description: 'Desbloquea logros de demostración para el admin')]
class UnlockAchievementsDemoCommand extends Command
{
    public function __construct(
        private AchievementService $achievementService,
        private AchievementRepository $achievementRepository,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Obtener usuario admin
        $admin = $this->userRepository->findOneBy(['email' => 'admin@metafit.com']);
        if (!$admin) {
            $io->error('Usuario admin no encontrado');
            return Command::FAILURE;
        }

        // Desbloquear los primeros 3 logros
        $achievements = $this->achievementRepository->findBy([], ['sort_order' => 'ASC'], 3);

        foreach ($achievements as $achievement) {
            $this->achievementService->unlockAchievement($admin, $achievement);
            $io->writeln("✓ Desbloqueado: {$achievement->getName()} (+{$achievement->getOtorgatedXp()} XP)");
        }

        $io->success('Logros de demostración desbloqueados!');

        return Command::SUCCESS;
    }
}
