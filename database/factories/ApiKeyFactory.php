<?php

namespace Database\Factories;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $plaintext = ApiKey::PREFIX.bin2hex(random_bytes(20));

        return [
            'name' => $this->faker->unique()->company(),
            'key_hash' => hash('sha256', $plaintext),
            'last_four' => substr($plaintext, -4),
            'enabled' => true,
            'last_used_at' => null,
        ];
    }

    public function disabled(): self
    {
        return $this->state(fn () => ['enabled' => false]);
    }

    /**
     * Create with a known plaintext, returned via a callback so tests can
     * capture it.
     *
     * @param  callable(string $plaintext): void  $capture
     */
    public function withPlaintext(callable $capture): self
    {
        return $this->state(function () use ($capture) {
            $plaintext = ApiKey::PREFIX.bin2hex(random_bytes(20));
            $capture($plaintext);

            return [
                'key_hash' => hash('sha256', $plaintext),
                'last_four' => substr($plaintext, -4),
            ];
        });
    }
}
