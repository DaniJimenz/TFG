<?php

namespace App\Entity;

use App\Repository\ExerciseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExerciseRepository::class)]
class Exercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $muscular_group = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $technique = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url_image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url_video = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $necessary_material = null;

    #[ORM\Column(length: 50)]
    private ?string $difficulty = null;

    #[ORM\Column]
    private ?bool $compound = null;

    /**
     * @var Collection<int, Routine>
     */
    #[ORM\ManyToMany(targetEntity: Routine::class, mappedBy: 'exercises')]
    private Collection $routines;

    /**
     * @var Collection<int, Training>
     */
    #[ORM\OneToMany(targetEntity: Training::class, mappedBy: 'exercise')]
    private Collection $trainings;

    /**
     * @var Collection<int, ExerciseTraining>
     */
    #[ORM\OneToMany(targetEntity: ExerciseTraining::class, mappedBy: 'exercise')]
    private Collection $exerciseTrainings;

    public function __construct()
    {
        $this->routines = new ArrayCollection();
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

    public function getMuscularGroup(): ?string
    {
        return $this->muscular_group;
    }

    public function setMuscularGroup(string $muscular_group): static
    {
        $this->muscular_group = $muscular_group;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTechnique(): ?string
    {
        return $this->technique;
    }

    public function setTechnique(?string $technique): static
    {
        $this->technique = $technique;

        return $this;
    }

    public function getUrlImage(): ?string
    {
        return $this->url_image;
    }

    public function setUrlImage(?string $url_image): static
    {
        $this->url_image = $url_image;

        return $this;
    }

    public function getUrlVideo(): ?string
    {
        return $this->url_video;
    }

    public function setUrlVideo(?string $url_video): static
    {
        $this->url_video = $url_video;

        return $this;
    }

    public function getNecessaryMaterial(): ?string
    {
        return $this->necessary_material;
    }

    public function setNecessaryMaterial(?string $necessary_material): static
    {
        $this->necessary_material = $necessary_material;

        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function isCompound(): ?bool
    {
        return $this->compound;
    }

    public function setCompound(bool $compound): static
    {
        $this->compound = $compound;

        return $this;
    }

    /**
     * @return Collection<int, Routine>
     */
    public function getRoutines(): Collection
    {
        return $this->routines;
    }

    public function addRoutine(Routine $routine): static
    {
        if (!$this->routines->contains($routine)) {
            $this->routines->add($routine);
            $routine->addExercise($this);
        }

        return $this;
    }

    public function removeRoutine(Routine $routine): static
    {
        if ($this->routines->removeElement($routine)) {
            $routine->removeExercise($this);
        }

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
            $training->setExercise($this);
        }

        return $this;
    }

    public function removeTraining(Training $training): static
    {
        if ($this->trainings->removeElement($training)) {
            // set the owning side to null (unless already changed)
            if ($training->getExercise() === $this) {
                $training->setExercise(null);
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
            $exerciseTraining->setExercise($this);
        }

        return $this;
    }

    public function removeExerciseTraining(ExerciseTraining $exerciseTraining): static
    {
        if ($this->exerciseTrainings->removeElement($exerciseTraining)) {
            // set the owning side to null (unless already changed)
            if ($exerciseTraining->getExercise() === $this) {
                $exerciseTraining->setExercise(null);
            }
        }

        return $this;
    }
}
