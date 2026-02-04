<?php

namespace App\Entity;

use App\Repository\FoodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FoodRepository::class)]
class Food
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bar_scanner = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $calories_by_100 = null;

    #[ORM\Column]
    private ?float $proteine_by_100 = null;

    #[ORM\Column]
    private ?float $carbohidrates_by_100 = null;

    #[ORM\Column]
    private ?float $fats_by_100 = null;

    #[ORM\Column]
    private ?float $fiber_by_100 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $font = null;

    #[ORM\Column(length: 100)]
    private ?string $category = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    /**
     * @var Collection<int, MealFood>
     */
    #[ORM\OneToMany(targetEntity: MealFood::class, mappedBy: 'food')]
    private Collection $mealFood;

    /**
     * @var Collection<int, Allergen>
     */
    #[ORM\ManyToMany(targetEntity: Allergen::class, inversedBy: 'food')]
    private Collection $allergens;

    public function __construct()
    {
        $this->mealFood = new ArrayCollection();
        $this->allergens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBarScanner(): ?string
    {
        return $this->bar_scanner;
    }

    public function setBarScanner(?string $bar_scanner): static
    {
        $this->bar_scanner = $bar_scanner;

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

    public function getCaloriesBy100(): ?float
    {
        return $this->calories_by_100;
    }

    public function setCaloriesBy100(float $calories_by_100): static
    {
        $this->calories_by_100 = $calories_by_100;

        return $this;
    }

    public function getProteineBy100(): ?float
    {
        return $this->proteine_by_100;
    }

    public function setProteineBy100(float $proteine_by_100): static
    {
        $this->proteine_by_100 = $proteine_by_100;

        return $this;
    }

    public function getCarbohidratesBy100(): ?float
    {
        return $this->carbohidrates_by_100;
    }

    public function setCarbohidratesBy100(float $carbohidrates_by_100): static
    {
        $this->carbohidrates_by_100 = $carbohidrates_by_100;

        return $this;
    }

    public function getFatsBy100(): ?float
    {
        return $this->fats_by_100;
    }

    public function setFatsBy100(float $fats_by_100): static
    {
        $this->fats_by_100 = $fats_by_100;

        return $this;
    }

    public function getFiberBy100(): ?float
    {
        return $this->fiber_by_100;
    }

    public function setFiberBy100(float $fiber_by_100): static
    {
        $this->fiber_by_100 = $fiber_by_100;

        return $this;
    }

    public function getFont(): ?string
    {
        return $this->font;
    }

    public function setFont(?string $font): static
    {
        $this->font = $font;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

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
            $mealFood->setFood($this);
        }

        return $this;
    }

    public function removeMealFood(MealFood $mealFood): static
    {
        if ($this->mealFood->removeElement($mealFood)) {
            // set the owning side to null (unless already changed)
            if ($mealFood->getFood() === $this) {
                $mealFood->setFood(null);
            }
        }

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
}
