<?php

namespace Database\Factories;

use App\Models\Permission;
use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2).'.view',
            'display_name' => fn (array $attributes) => Str::headline(str_replace('.', ' ', $attributes['name'])),
            'short_note' => null,
            'guard_name' => 'web',
        ];
    }

    public function fromRegistry(string $name): static
    {
        $permission = collect(PermissionRegistry::groups())->flatten(1)->firstWhere('name', $name);

        if ($permission === null) {
            throw new InvalidArgumentException("Permission [{$name}] is not defined in the registry.");
        }

        return $this->state($permission);
    }
}
