<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_user' => $this->faker->name(),
            'nohp'      => '08' . $this->faker->numerify('##########'),
            'email'     => $this->faker->unique()->safeEmail(),
            'password'  => Hash::make('password'),
            'role'      => 'operator',
            'status'    => 'active',
        ];
    }
    public function asAdmin(): static
    {
        return $this->state(['role' => 'admin']);
    }
    public function asInventaris(): static
    {
        return $this->state(['role' => 'inventaris']);
    }
    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
