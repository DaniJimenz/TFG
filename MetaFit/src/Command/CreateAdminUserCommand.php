<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Crea un usuario administrador')]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Crear usuario admin
        $user = new User();
        $user->setEmail('admin@metafit.com');
        $user->setName('Admin');
        $user->setLastname('User');
        $user->setAge(30);
        $user->setHeight(1.75);
        $user->setGender('H');
        $user->setActualWeight(75.0);
        $user->setPurpose('fitness');
        $user->setActivityLevel('Alta');
        $user->setRol('ROLE_ADMIN');
        $user->setPointsXp(0);
        $user->setContinuity(0);
        $user->setLevel(0);

        // Hashear contraseña
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'admin123');
        $user->setPassword($hashedPassword);

        // Persistir
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Usuario admin creado exitosamente!');
        $io->writeln('Email: admin@metafit.com');
        $io->writeln('Contraseña: admin123');

        return Command::SUCCESS;
    }
}
