<?php

namespace App\Entity;

use App\Repository\RoutineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoutineRepository::class)]
class Routine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $objective = null;

    #[ORM\Column]
    private ?int $days_week = null;

    #[ORM\Column(length: 255)]
    private ?string $dispo_material = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_start = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $date_end = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\ManyToOne(inversedBy: 'routines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, Exercise>
     */
    #[ORM\ManyToMany(targetEntity: Exercise::class, inversedBy: 'routines')]
    private Collection $exercises;

    /**
     * @var Collection<int, Training>
     */
    #[ORM\OneToMany(targetEntity: Training::class, mappedBy: 'routine', cascade: ['remove'], orphanRemoval: true)]
    private Collection $trainings;

    /**
     * @var Collection<int, ExerciseTraining>
     */
    #[ORM\OneToMany(targetEntity: ExerciseTraining::class, mappedBy: 'routine', cascade: ['remove'], orphanRemoval: true)]
    private Collection $exerciseTrainings;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
        $this->trainings = new ArrayCollection();
        $this->exerciseTrainings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(string $objective): static
    {
        $this->objective = $objective;

        return $this;
    }

    public function getDaysWeek(): ?int
    {
        return $this->days_week;
    }

    public function setDaysWeek(int $days_week): static
    {
        $this->days_week = $days_week;

        return $this;
    }

    public function getDispoMaterial(): ?string
    {
        return $this->dispo_material;
    }

    public function setDispoMaterial(string $dispo_material): static
    {
        $this->dispo_material = $dispo_material;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getDateStart(): ?\DateTimeImmutable
    {
        return $this->date_start;
    }

    public function setDateStart(\DateTimeImmutable $date_start): static
    {
        $this->date_start = $date_start;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeImmutable
    {
        return $this->date_end;
    }

    public function setDateEnd(?\DateTimeImmutable $date_end): static
    {
        $this->date_end = $date_end;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Exercise>
     */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function addExercise(Exercise $exercise): static
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
        }

        return $this;
    }

    public function removeExercise(Exercise $exercise): static
    {
        $this->exercises->removeElement($exercise);

        return $this;
    }

    /**
     * @return Collection<int, Training>
     */
    public function getTrainings(): Collection
    {
        return $this->trainings;
    }

    public function addTraining(Training $training): static
    {
        if (!$this->trainings->contains($training)) {
            $this->trainings->add($training);
            $training->setRoutine($this);
        }

        return $this;
    }

    public function removeTraining(Training $training): static
    {
        if ($this->trainings->removeElement($training)) {
            // set the owning side to null (unless already changed)
            if ($training->getRoutine() === $this) {
                $training->setRoutine(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ExerciseTraining>
     */
    public function getExerciseTrainings(): Collection
    {
        return $this->exerciseTrainings;
    }

    public function addExerciseTraining(ExerciseTraining $exerciseTraining): static
    {
        if (!$this->exerciseTrainings->contains($exerciseTraining)) {
            $this->exerciseTrainings->add($exerciseTraining);
            $exerciseTraining->setRoutine($this);
        }

        return $this;
    }

    public function removeExerciseTraining(ExerciseTraining $exerciseTraining): static
    {
        if ($this->exerciseTrainings->removeElement($exerciseTraining)) {
            // set the owning side to null (unless already changed)
            if ($exerciseTraining->getRoutine() === $this) {
                $exerciseTraining->setRoutine(null);
            }
        }

        return $this;
    }
}
