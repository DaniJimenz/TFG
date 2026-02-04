<?php

namespace App\Entity;

use App\Repository\MealRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MealRepository::class)]
class Meal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $food_type = null;

    #[ORM\Column]
    private ?float $calories_total = null;

    #[ORM\Column]
    private ?float $proteines_g = null;

    #[ORM\Column]
    private ?float $carbohidrates_g = null;

    #[ORM\Column]
    private ?float $fats_g = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url_image = null;

    #[ORM\Column]
    private ?bool $bar_scanner = null;

    #[ORM\Column(length: 50)]
    private ?string $register_method = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $register_date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'meals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $appUser = null;

    /**
     * @var Collection<int, MealFood>
     */
    #[ORM\OneToMany(targetEntity: MealFood::class, mappedBy: 'meal')]
    private Collection $mealFood;

    public function __construct()
    {
        $this->mealFood = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoodType(): ?string
    {
        return $this->food_type;
    }

    public function setFoodType(string $food_type): static
    {
        $this->food_type = $food_type;

        return $this;
    }

    public function getCaloriesTotal(): ?float
    {
        return $this->calories_total;
    }

    public function setCaloriesTotal(float $calories_total): static
    {
        $this->calories_total = $calories_total;

        return $this;
    }

    public function getProteinesG(): ?float
    {
        return $this->proteines_g;
    }

    public function setProteinesG(float $proteines_g): static
    {
        $this->proteines_g = $proteines_g;

        return $this;
    }

    public function getCarbohidratesG(): ?float
    {
        return $this->carbohidrates_g;
    }

    public function setCarbohidratesG(float $carbohidrates_g): static
    {
        $this->carbohidrates_g = $carbohidrates_g;

        return $this;
    }

    public function getFatsG(): ?float
    {
        return $this->fats_g;
    }

    public function setFatsG(float $fats_g): static
    {
        $this->fats_g = $fats_g;

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

    public function isBarScanner(): ?bool
    {
        return $this->bar_scanner;
    }

    public function setBarScanner(bool $bar_scanner): static
    {
        $this->bar_scanner = $bar_scanner;

        return $this;
    }

    public function getRegisterMethod(): ?string
    {
        return $this->register_method;
    }

    public function setRegisterMethod(string $register_method): static
    {
        $this->register_method = $register_method;

        return $this;
    }

    public function getRegisterDate(): ?\DateTimeImmutable
    {
        return $this->register_date;
    }

    public function setRegisterDate(\DateTimeImmutable $register_date): static
    {
        $this->register_date = $register_date;

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

    public function getAppUser(): ?User
    {
        return $this->appUser;
    }

    public function setAppUser(?User $appUser): static
    {
        $this->appUser = $appUser;

        return $this;
    }

    /**
     * @return Collection<int, MealFood>
     */
    public function getMealFood(): Collection
    {
        return $this->mealFood;
    }

    public function addMealFood(MealFood $mealFood): static
    {
        if (!$this->mealFood->contains($mealFood)) {
            $this->mealFood->add($mealFood);
            $mealFood->setMeal($this);
        }

        return $this;
    }

    public function removeMealFood(MealFood $mealFood): static
    {
        if ($this->mealFood->removeElement($mealFood)) {
            // set the owning side to null (unless already changed)
            if ($mealFood->getMeal() === $this) {
                $mealFood->setMeal(null);
            }
        }

        return $this;
    }
}
