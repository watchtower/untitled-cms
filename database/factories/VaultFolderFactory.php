<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VaultFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VaultFolder>
 */
class VaultFolderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word();

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'owner_id' => User::factory(),
            'path_slug' => '/'.Str::slug($name),
        ];
    }
}
