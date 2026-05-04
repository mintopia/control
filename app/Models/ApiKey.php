<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ApiKeyFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $key_hash
 * @property string $last_four
 * @property bool $enabled
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApiKey extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasFactory;

    public const PREFIX = 'ctrl_';

    /**
     * Return empty string so the Authenticatable trait does not attempt to
     * read a non-existent remember_token column from the api_keys table.
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * Return empty string so the Authenticatable trait does not attempt to
     * read a non-existent password column from the api_keys table.
     */
    public function getAuthPasswordName(): string
    {
        return '';
    }

    protected $fillable = [
        'name',
        'key_hash',
        'last_four',
        'enabled',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Generate a new API key. Returns [model, plaintext]. The plaintext is
     * never stored; only the SHA-256 hash and the last four characters.
     *
     * @return array{0: self, 1: string}
     */
    public static function generate(string $name): array
    {
        $plaintext = self::PREFIX.bin2hex(random_bytes(20));
        $key = self::create([
            'name' => $name,
            'key_hash' => hash('sha256', $plaintext),
            'last_four' => substr($plaintext, -4),
            'enabled' => true,
        ]);

        return [$key, $plaintext];
    }

    /**
     * Look up an API key by its plaintext form. Does NOT filter by `enabled`;
     * the caller is responsible for that check (the guard does so).
     */
    public static function findByPlaintext(string $plaintext): ?self
    {
        return self::where('key_hash', hash('sha256', $plaintext))->first();
    }

    protected static function newFactory(): ApiKeyFactory
    {
        return ApiKeyFactory::new();
    }
}
