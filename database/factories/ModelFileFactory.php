<?php

namespace Database\Factories;

use App\Models\ModelFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModelFile>
 */
class ModelFileFactory extends Factory
{
    protected $model = ModelFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word().'.stl';

        return [
            'original_filename' => $filename,
            'stored_filename' => fake()->uuid().'.stl',
            'storage_path' => 'models/'.fake()->uuid().'.stl',
            'extension' => 'stl',
            'mime_type' => 'model/stl',
            'file_size' => fake()->numberBetween(1000, 5000000),
            'status' => ModelFile::STATUS_PENDING,
            'vertex_count' => null,
            'triangle_count' => null,
            'volume' => null,
            'surface_area' => null,
            'width' => null,
            'height' => null,
            'depth' => null,
            'center_x' => null,
            'center_y' => null,
            'center_z' => null,
            'min_x' => null,
            'min_y' => null,
            'min_z' => null,
            'max_x' => null,
            'max_y' => null,
            'max_z' => null,
            'metadata' => null,
            'error_message' => null,
            'parsed_at' => null,
        ];
    }

    /**
     * Indicate that the model file is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModelFile::STATUS_PROCESSING,
        ]);
    }

    /**
     * Indicate that the model file processing is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModelFile::STATUS_COMPLETED,
            'vertex_count' => fake()->numberBetween(100, 50000),
            'triangle_count' => fake()->numberBetween(200, 100000),
            'volume' => fake()->randomFloat(2, 10, 5000),
            'surface_area' => fake()->randomFloat(2, 50, 10000),
            'width' => 100.0,
            'height' => 50.0,
            'depth' => 25.0,
            'center_x' => 0.0,
            'center_y' => 0.0,
            'center_z' => 0.0,
            'min_x' => -50.0,
            'min_y' => -25.0,
            'min_z' => -12.5,
            'max_x' => 50.0,
            'max_y' => 25.0,
            'max_z' => 12.5,
            'metadata' => [
                'generator' => '3D Software',
                'units' => 'mm',
                'has_colors' => false,
            ],
            'parsed_at' => now(),
        ]);
    }

    /**
     * Indicate that the model file processing failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModelFile::STATUS_FAILED,
            'error_message' => 'Failed to parse file header or corrupted 3D model data.',
        ]);
    }
}
