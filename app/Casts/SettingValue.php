<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SettingValue implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param array<string, mixed> $attributes
     */

    // Unit Test testGetDecryptsEncryptedValue failed with validating $model->encrypted
    // This is because the model does not have an 'encrypted' property.
    // To fix this, we check the attributes array for 'encrypted' instead.
    //
    // This is commented out to avoid confusion with the test.
    // public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    // {
    //     if ($model->encrypted && $value !== null) {
    //         $value = Crypt::decrypt($value);
    //     }
    //     return $value;
    // }
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (($attributes['encrypted'] ?? false) && $value !== null) {
            $value = Crypt::decrypt($value);
        }
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param array<string, mixed> $attributes
     */

    // Unit Test testSetEncryptsValueIfRequired failed with validating $model->encrypted
    // This is because the model does not have an 'encrypted' property.
    // To fix this, we check the attributes array for 'encrypted' instead.
    //
    // This is commented out to avoid confusion with the test.
    // public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    // {
    //     if ($model->encrypted && $value !== null) {
    //         $value = Crypt::encrypt($value);
    //     }
    //     return $value;
    // }
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (($attributes['encrypted'] ?? false) && $value !== null) {
            $value = Crypt::encrypt($value);
        }
        return $value;
    }
}
