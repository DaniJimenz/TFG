<?php

namespace App\Command;

use App\Entity\Exercise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:update-press-banca', description: 'Actualizar la imagen del Press de Banca')]
class UpdatePressBancaCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $exercise = $this->entityManager->getRepository(Exercise::class)
            ->findOneBy(['name' => 'Press de Banca']);

        if (!$exercise) {
            $io->error('No se encontró el ejercicio "Press de Banca"');
            return Command::FAILURE;
        }

        $exercise->setUrlImage('/img/pressbanca.webm');
        $this->entityManager->flush();

        $io->success('¡Imagen del Press de Banca actualizada a /img/pressbanca.webm!');

        return Command::SUCCESS;
    }
}
