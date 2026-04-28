<?php

namespace App\Entity;

use App\Repository\TrainingPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingPreferenceRepository::class)]
class TrainingPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'trainingPreference')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Preferencias de Entrenamiento
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $preferred_time = 'morning'; // morning, afternoon, evening

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $training_intensity = 'moderate'; // light, moderate, intense

    #[ORM\Column]
    private ?int $training_duration_minutes = 60;

    #[ORM\Column]
    private ?int $rest_between_sets_seconds = 60;

    #[ORM\Column]
    private ?bool $notifications_enabled = true;

    #[ORM\Column]
    private ?bool $reminder_before_training = true;

    #[ORM\Column]
    private ?int $reminder_minutes_before = 30;

    #[ORM\Column]
    private ?bool $sound_enabled = true;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $measurement_unit = 'kg'; // kg, lbs

    // Privacidad
    #[ORM\Column]
    private ?bool $profile_public = false;

    #[ORM\Column]
    private ?bool $stats_visible = true;

    #[ORM\Column]
    private ?bool $achievements_visible = true;

    #[ORM\Column]
    private ?bool $routines_visible = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPreferredTime(): ?string
    {
        return $this->preferred_time;
    }

    public function setPreferredTime(?string $preferred_time): static
    {
        $this->preferred_time = $preferred_time;
        return $this;
    }

    public function getTrainingIntensity(): ?string
    {
        return $this->training_intensity;
    }

    public function setTrainingIntensity(?string $training_intensity): static
    {
        $this->training_intensity = $training_intensity;
        return $this;
    }

    public function getTrainingDurationMinutes(): ?int
    {
        return $this->training_duration_minutes;
    }

    public function setTrainingDurationMinutes(?int $training_duration_minutes): static
    {
        $this->training_duration_minutes = $training_duration_minutes;
        return $this;
    }

    public function getRestBetweenSetsSeconds(): ?int
    {
        return $this->rest_between_sets_seconds;
    }

    public function setRestBetweenSetsSeconds(?int $rest_between_sets_seconds): static
    {
        $this->rest_between_sets_seconds = $rest_between_sets_seconds;
        return $this;
    }

    public function isNotificationsEnabled(): ?bool
    {
        return $this->notifications_enabled;
    }

    public function setNotificationsEnabled(bool $notifications_enabled): static
    {
        $this->notifications_enabled = $notifications_enabled;
        return $this;
    }

    public function isReminderBeforeTraining(): ?bool
    {
        return $this->reminder_before_training;
    }

    public function setReminderBeforeTraining(bool $reminder_before_training): static
    {
        $this->reminder_before_training = $reminder_before_training;
        return $this;
    }

    public function getReminderMinutesBefore(): ?int
    {
        return $this->reminder_minutes_before;
    }

    public function setReminderMinutesBefore(?int $reminder_minutes_before): static
    {
        $this->reminder_minutes_before = $reminder_minutes_before;
        return $this;
    }

    public function isSoundEnabled(): ?bool
    {
        return $this->sound_enabled;
    }

    public function setSoundEnabled(bool $sound_enabled): static
    {
        $this->sound_enabled = $sound_enabled;
        return $this;
    }

    public function getMeasurementUnit(): ?string
    {
        return $this->measurement_unit;
    }

    public function setMeasurementUnit(?string $measurement_unit): static
    {
        $this->measurement_unit = $measurement_unit;
        return $this;
    }

    public function isProfilePublic(): ?bool
    {
        return $this->profile_public;
    }

    public function setProfilePublic(bool $profile_public): static
    {
        $this->profile_public = $profile_public;
        return $this;
    }

    public function isStatsVisible(): ?bool
    {
        return $this->stats_visible;
    }

    public function setStatsVisible(bool $stats_visible): static
    {
        $this->stats_visible = $stats_visible;
        return $this;
    }

    public function isAchievementsVisible(): ?bool
    {
        return $this->achievements_visible;
    }

    public function setAchievementsVisible(bool $achievements_visible): static
    {
        $this->achievements_visible = $achievements_visible;
        return $this;
    }

    public function isRoutinesVisible(): ?bool
    {
        return $this->routines_visible;
    }

    public function setRoutinesVisible(bool $routines_visible): static
    {
        $this->routines_visible = $routines_visible;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }
}
