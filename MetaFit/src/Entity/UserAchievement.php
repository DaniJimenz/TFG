<?php

namespace App\Entity;

use App\Repository\UserAchievementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAchievementRepository::class)]
class UserAchievement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $achievement_name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_obtained = null;

    #[ORM\Column]
    private ?bool $notificated = null;

    #[ORM\ManyToOne(inversedBy: 'achievement')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $appUser = null;

    #[ORM\ManyToOne(inversedBy: 'userAchievements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Achievement $achievement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAchievementName(): ?string
    {
        return $this->achievement_name;
    }

    public function setAchievementName(string $achievement_name): static
    {
        $this->achievement_name = $achievement_name;

        return $this;
    }

    public function getDateObtained(): ?\DateTimeImmutable
    {
        return $this->date_obtained;
    }

    public function setDateObtained(\DateTimeImmutable $date_obtained): static
    {
        $this->date_obtained = $date_obtained;

        return $this;
    }

    public function isNotificated(): ?bool
    {
        return $this->notificated;
    }

    public function setNotificated(bool $notificated): static
    {
        $this->notificated = $notificated;

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

    public function getAchievement(): ?Achievement
    {
        return $this->achievement;
    }

    public function setAchievement(?Achievement $achievement): static
    {
        $this->achievement = $achievement;

        return $this;
    }
}
