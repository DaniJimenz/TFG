<?php

namespace App\Entity;

use App\Repository\TrainingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingRepository::class)]
class Training
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    private ?int $completed_series = null;

    #[ORM\Column]
    private ?int $repetitions = null;

    #[ORM\Column]
    private ?float $weight = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration_minutes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private bool $completed = true;

    #[ORM\Column(nullable: true)]
    private ?float $one_rm_estimated = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $series_data = null;

    #[ORM\ManyToOne(inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $appUser = null;

    #[ORM\ManyToOne(inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercise $exercise = null;

    #[ORM\ManyToOne(inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Routine $routine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCompletedSeries(): ?int
    {
        return $this->completed_series;
    }

    public function setCompletedSeries(int $completed_series): static
    {
        $this->completed_series = $completed_series;

        return $this;
    }

    public function getRepetitions(): ?int
    {
        return $this->repetitions;
    }

    public function setRepetitions(int $repetitions): static
    {
        $this->repetitions = $repetitions;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->duration_minutes;
    }

    public function setDurationMinutes(?int $duration_minutes): static
    {
        $this->duration_minutes = $duration_minutes;

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

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function getOneRmEstimated(): ?float
    {
        return $this->one_rm_estimated;
    }

    public function setOneRmEstimated(?float $one_rm_estimated): static
    {
        $this->one_rm_estimated = $one_rm_estimated;

        return $this;
    }

    public function getAppUser(): ?User
    {
        return $this->appUser;
    }

    public function setAppUser(?User $appUser): static
    {
        $this->appUser = $appUser;

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

    public function getRoutine(): ?Routine
    {
        return $this->routine;
    }

    public function setRoutine(?Routine $routine): static
    {
        $this->routine = $routine;

        return $this;
    }

    public function getSeriesData(): ?array
    {
        return $this->series_data;
    }

    public function setSeriesData(?array $series_data): static
    {
        $this->series_data = $series_data;

        return $this;
    }
}
