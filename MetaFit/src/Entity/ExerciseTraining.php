<?php

namespace App\Entity;

use App\Repository\ExerciseTrainingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExerciseTrainingRepository::class)]
class ExerciseTraining
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $day_week = null;

    #[ORM\Column]
    private ?int $order_rutine = null;

    #[ORM\Column]
    private ?int $series_objective = null;

    #[ORM\Column]
    private ?int $reps_min = null;

    #[ORM\Column]
    private ?int $reps_max = null;

    #[ORM\Column]
    private ?int $rest_seconds = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'exerciseTrainings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Routine $routine = null;

    #[ORM\ManyToOne(inversedBy: 'exerciseTrainings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercise $exercise = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayWeek(): ?int
    {
        return $this->day_week;
    }

    public function setDayWeek(int $day_week): static
    {
        $this->day_week = $day_week;

        return $this;
    }

    public function getOrderRutine(): ?int
    {
        return $this->order_rutine;
    }

    public function setOrderRutine(int $order_rutine): static
    {
        $this->order_rutine = $order_rutine;

        return $this;
    }

    public function getSeriesObjective(): ?int
    {
        return $this->series_objective;
    }

    public function setSeriesObjective(int $series_objective): static
    {
        $this->series_objective = $series_objective;

        return $this;
    }

    public function getRepsMin(): ?int
    {
        return $this->reps_min;
    }

    public function setRepsMin(int $reps_min): static
    {
        $this->reps_min = $reps_min;

        return $this;
    }

    public function getRepsMax(): ?int
    {
        return $this->reps_max;
    }

    public function setRepsMax(int $reps_max): static
    {
        $this->reps_max = $reps_max;

        return $this;
    }

    public function getRestSeconds(): ?int
    {
        return $this->rest_seconds;
    }

    public function setRestSeconds(int $rest_seconds): static
    {
        $this->rest_seconds = $rest_seconds;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getRoutine(): ?Routine
    {
        return $this->routine;
    }

    public function setRoutine(?Routine $routine): static
    {
        $this->routine = $routine;

        return $this;
    }

    public function getExercise(): ?Exercise
    {
        return $this->exercise;
    }

    public function setExercise(?Exercise $exercise): static
    {
        $this->exercise = $exercise;

        return $this;
    }
}
