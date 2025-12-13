<?php

namespace Database\Factories;

use App\Models\Drug;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Drug>
 */
class DrugFactory extends Factory
{
    protected $model = Drug::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true) . ' ' . $this->faker->randomElement(['100mg', '200mg', '500mg', '1g']);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'generic_name' => $this->faker->words(2, true),
            'brand_name' => $this->faker->company(),
            'brand_id' => Brand::factory(),
            'category_id' => $this->faker->numberBetween(1, 10),
            'image' => 'uploads/drugs/' . $this->faker->slug() . '.png',
            'box_price' => $this->faker->randomFloat(2, 1.00, 500.00),
            'box_cost_price' => $this->faker->randomFloat(2, 0.50, 250.00),
            'strip_price' => $this->faker->randomFloat(2, 0.10, 50.00),
            'strip_cost_price' => $this->faker->randomFloat(2, 0.05, 25.00),
            'tablet_price' => $this->faker->randomFloat(2, 0.01, 5.00),
            'tablet_cost_price' => $this->faker->randomFloat(2, 0.005, 2.50),
            'strips_per_box' => $this->faker->numberBetween(5, 20),
            'tablets_per_strip' => $this->faker->numberBetween(5, 20),
            'quantity_in_boxes' => $this->faker->numberBetween(1, 50),
            'quantity' => $this->faker->numberBetween(0, 2000),
            'expiry_date' => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
            'barcode' => $this->faker->unique()->numerify('#############'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }

    /**
     * Indicate that the drug is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the drug is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the drug is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(1, 2000),
        ]);
    }

    /**
     * Indicate that the drug is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }

    /**
     * Indicate that the drug is expiring soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ]);
    }
}
