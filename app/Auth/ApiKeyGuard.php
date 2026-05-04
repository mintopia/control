<?php

namespace App\Auth;

use App\Models\ApiKey;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Traits\Macroable;

class ApiKeyGuard implements Guard
{
    use Macroable;

    protected ?Authenticatable $user = null;

    protected bool $resolved = false;

    public function __construct(protected Request $request) {}

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;

        $token = $this->bearerToken();
        if ($token === null) {
            return null;
        }

        $key = ApiKey::where('key_hash', hash('sha256', $token))
            ->where('enabled', true)
            ->first();

        if ($key === null) {
            return null;
        }

        $now = Carbon::now();
        ApiKey::where('id', $key->id)->update(['last_used_at' => $now]);
        $key->last_used_at = $now;

        return $this->user = $key;
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->resolved = true;

        return $this;
    }

    protected function bearerToken(): ?string
    {
        return $this->request->bearerToken() ?: null;
    }
}
