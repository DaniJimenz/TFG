<?php

namespace App\Entity;

use App\Entity\Subscription;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $email = null;

    #[ORM\Column(length: 200)]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column]
    private ?int $points_xp = null;

    #[ORM\Column]
    private ?int $continuity = null;

    #[ORM\Column]
    private ?int $level = null;

    #[ORM\Column(length: 100)]
    private ?string $rol = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deleted_at = null;

    #[ORM\Column]
    private ?int $age = null;

    #[ORM\Column]
    private ?float $height = null;

    #[ORM\Column(length: 1)]
    private ?string $gender = null;

    #[ORM\Column]
    private ?float $actual_weight = null;

    #[ORM\Column(length: 255)]
    private ?string $purpose = null;

    #[ORM\Column(length: 50)]
    private ?string $activity_level = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Subscription::class)]
    private ?Subscription $subscription = null;

    /**
     * @var Collection<int, Allergen>
     */
    #[ORM\ManyToMany(targetEntity: Allergen::class, inversedBy: 'users')]
    private Collection $allergens;

    /**
     * @var Collection<int, Routine>
     */
    #[ORM\OneToMany(targetEntity: Routine::class, mappedBy: 'owner')]
    private Collection $routines;

    /**
     * @var Collection<int, Training>
     */
    #[ORM\OneToMany(targetEntity: Training::class, mappedBy: 'appUser')]
    private Collection $trainings;

    /**
     * @var Collection<int, UserAchievement>
     */
    #[ORM\OneToMany(targetEntity: UserAchievement::class, mappedBy: 'appUser')]
    private Collection $achievement;

    /**
     * @var Collection<int, Meal>
     */
    #[ORM\OneToMany(targetEntity: Meal::class, mappedBy: 'appUser')]
    private Collection $meals;

    public function __construct()
    {
        $this->allergens = new ArrayCollection();
        $this->routines = new ArrayCollection();
        $this->trainings = new ArrayCollection();
        $this->achievement = new ArrayCollection();
        $this->meals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPointsXp(): ?int
    {
        return $this->points_xp;
    }

    public function setPointsXp(int $points_xp): static
    {
        $this->points_xp = $points_xp;

        return $this;
    }

    public function getContinuity(): ?int
    {
        return $this->continuity;
    }

    public function setContinuity(int $continuity): static
    {
        $this->continuity = $continuity;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getRol(): ?string
    {
        return $this->rol;
    }

    public function setRol(string $rol): static
    {
        $this->rol = $rol;

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

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?\DateTimeImmutable $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(float $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getActualWeight(): ?float
    {
        return $this->actual_weight;
    }

    public function setActualWeight(float $actual_weight): static
    {
        $this->actual_weight = $actual_weight;

        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): static
    {
        $this->purpose = $purpose;

        return $this;
    }

    public function getActivityLevel(): ?string
    {
        return $this->activity_level;
    }

    public function setActivityLevel(string $activity_level): static
    {
        $this->activity_level = $activity_level;

        return $this;
    }

    /**
     * @return Collection<int, Allergen>
     */
    public function getAllergens(): Collection
    {
        return $this->allergens;
    }

    public function addAllergen(Allergen $allergen): static
    {
        if (!$this->allergens->contains($allergen)) {
            $this->allergens->add($allergen);
        }

        return $this;
    }

    public function removeAllergen(Allergen $allergen): static
    {
        $this->allergens->removeElement($allergen);

        return $this;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): static
    {
        if ($subscription === null && $this->subscription !== null) {
            $this->subscription->setUser(null);
        }

        if ($subscription !== null && $subscription->getUser() !== $this) {
            $subscription->setUser($this);
        }

        $this->subscription = $subscription;

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
            $routine->setOwner($this);
        }

        return $this;
    }

    public function removeRoutine(Routine $routine): static
    {
        if ($this->routines->removeElement($routine)) {
            // set the owning side to null (unless already changed)
            if ($routine->getOwner() === $this) {
                $routine->setOwner(null);
            }
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
            $training->setAppUser($this);
        }

        return $this;
    }

    public function removeTraining(Training $training): static
    {
        if ($this->trainings->removeElement($training)) {
            // set the owning side to null (unless already changed)
            if ($training->getAppUser() === $this) {
                $training->setAppUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserAchievement>
     */
    public function getAchievement(): Collection
    {
        return $this->achievement;
    }

    public function addAchievement(UserAchievement $achievement): static
    {
        if (!$this->achievement->contains($achievement)) {
            $this->achievement->add($achievement);
            $achievement->setAppUser($this);
        }

        return $this;
    }

    public function removeAchievement(UserAchievement $achievement): static
    {
        if ($this->achievement->removeElement($achievement)) {
            // set the owning side to null (unless already changed)
            if ($achievement->getAppUser() === $this) {
                $achievement->setAppUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Meal>
     */
    public function getMeals(): Collection
    {
        return $this->meals;
    }

    public function addMeal(Meal $meal): static
    {
        if (!$this->meals->contains($meal)) {
            $this->meals->add($meal);
            $meal->setAppUser($this);
        }

        return $this;
    }

    public function removeMeal(Meal $meal): static
    {
        if ($this->meals->removeElement($meal)) {
            // set the owning side to null (unless already changed)
            if ($meal->getAppUser() === $this) {
                $meal->setAppUser(null);
            }
        }

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = (array) $this->rol;
        // Garantizo que todos los usuarios tengan al menos ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        /*Borrar aquí datos temporales sensibles de User*/
    }
}
