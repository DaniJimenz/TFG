<?php

namespace App\Entity;

use App\Repository\MealFoodRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MealFoodRepository::class)]
class MealFood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $amount_grams = null;

    #[ORM\Column]
    private ?float $calories_calculated = null;

    #[ORM\Column]
    private ?float $proteine_calculated = null;

    #[ORM\Column]
    private ?float $carbohi_calculated = null;

    #[ORM\Column]
    private ?float $fats_calculated = null;

    #[ORM\ManyToOne(inversedBy: 'mealFood')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Meal $meal = null;

    #[ORM\ManyToOne(inversedBy: 'mealFood')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Food $food = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmountGrams(): ?float
    {
        return $this->amount_grams;
    }

    public function setAmountGrams(float $amount_grams): static
    {
        $this->amount_grams = $amount_grams;

        return $this;
    }

    public function getCaloriesCalculated(): ?float
    {
        return $this->calories_calculated;
    }

    public function setCaloriesCalculated(float $calories_calculated): static
    {
        $this->calories_calculated = $calories_calculated;

        return $this;
    }

    public function getProteineCalculated(): ?float
    {
        return $this->proteine_calculated;
    }

    public function setProteineCalculated(float $proteine_calculated): static
    {
        $this->proteine_calculated = $proteine_calculated;

        return $this;
    }

    public function getCarbohiCalculated(): ?float
    {
        return $this->carbohi_calculated;
    }

    public function setCarbohiCalculated(float $carbohi_calculated): static
    {
        $this->carbohi_calculated = $carbohi_calculated;

        return $this;
    }

    public function getFatsCalculated(): ?float
    {
        return $this->fats_calculated;
    }

    public function setFatsCalculated(float $fats_calculated): static
    {
        $this->fats_calculated = $fats_calculated;

        return $this;
    }

    public function getMeal(): ?Meal
    {
        return $this->meal;
    }

    public function setMeal(?Meal $meal): static
    {
        $this->meal = $meal;

        return $this;
    }

    public function getFood(): ?Food
    {
        return $this->food;
    }

    public function setFood(?Food $food): static
    {
        $this->food = $food;

        return $this;
    }
}
