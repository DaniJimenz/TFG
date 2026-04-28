<?php

namespace App\Entity;

use App\Repository\DataBackupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DataBackupRepository::class)]
class DataBackup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $file_name = null;

    #[ORM\Column]
    private ?int $file_size_bytes = null; // en bytes

    #[ORM\Column(length: 50)]
    private ?string $backup_type = null; // full, routines, trainings, nutrition

    #[ORM\Column]
    private ?bool $include_personal_data = true;

    #[ORM\Column]
    private ?bool $include_routines = true;

    #[ORM\Column]
    private ?bool $include_trainings = true;

    #[ORM\Column]
    private ?bool $include_meals = true;

    #[ORM\Column]
    private ?bool $include_achievements = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $file_path = null; // ruta relativa en storage

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expires_at = null; // fecha de expiración

    #[ORM\Column]
    private ?bool $is_automated = false; // true si es backup automático

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

    public function getFileName(): ?string
    {
        return $this->file_name;
    }

    public function setFileName(string $file_name): static
    {
        $this->file_name = $file_name;
        return $this;
    }

    public function getFileSizeBytes(): ?int
    {
        return $this->file_size_bytes;
    }

    public function setFileSizeBytes(int $file_size_bytes): static
    {
        $this->file_size_bytes = $file_size_bytes;
        return $this;
    }

    public function getFileSizeMB(): float
    {
        return round($this->file_size_bytes / (1024 * 1024), 2);
    }

    public function getBackupType(): ?string
    {
        return $this->backup_type;
    }

    public function setBackupType(string $backup_type): static
    {
        $this->backup_type = $backup_type;
        return $this;
    }

    public function isIncludePersonalData(): ?bool
    {
        return $this->include_personal_data;
    }

    public function setIncludePersonalData(bool $include_personal_data): static
    {
        $this->include_personal_data = $include_personal_data;
        return $this;
    }

    public function isIncludeRoutines(): ?bool
    {
        return $this->include_routines;
    }

    public function setIncludeRoutines(bool $include_routines): static
    {
        $this->include_routines = $include_routines;
        return $this;
    }

    public function isIncludeTrainings(): ?bool
    {
        return $this->include_trainings;
    }

    public function setIncludeTrainings(bool $include_trainings): static
    {
        $this->include_trainings = $include_trainings;
        return $this;
    }

    public function isIncludeMeals(): ?bool
    {
        return $this->include_meals;
    }

    public function setIncludeMeals(bool $include_meals): static
    {
        $this->include_meals = $include_meals;
        return $this;
    }

    public function isIncludeAchievements(): ?bool
    {
        return $this->include_achievements;
    }

    public function setIncludeAchievements(bool $include_achievements): static
    {
        $this->include_achievements = $include_achievements;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->file_path;
    }

    public function setFilePath(?string $file_path): static
    {
        $this->file_path = $file_path;
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

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expires_at;
    }

    public function setExpiresAt(?\DateTimeImmutable $expires_at): static
    {
        $this->expires_at = $expires_at;
        return $this;
    }

    public function isAutomated(): ?bool
    {
        return $this->is_automated;
    }

    public function setIsAutomated(bool $is_automated): static
    {
        $this->is_automated = $is_automated;
        return $this;
    }
}
