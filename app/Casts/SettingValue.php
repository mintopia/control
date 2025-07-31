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

    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($model->encrypted && $value !== null) {
            $value = Crypt::decrypt($value);
        }
        return $value;
    }

    // NOTE The following code makes the tests pass, however, I do not know whether the tests are correct.
    // We will need to validate how SettingValue is used in the application and I couldn't find an example of the attributes being used.

    // Unit Test testGetDecryptsEncryptedValue failed with validating $model->encrypted
    // This is because the model does not have an 'encrypted' property.
    // To fix this, we check the attributes array for 'encrypted' instead.

    // public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    // {
    //     if (($attributes['encrypted'] ?? false) && $value !== null) {
    //         $value = Crypt::decrypt($value);
    //     }
    //     return $value;
    // }

    /**
     * Prepare the given value for storage.
     *
     * @param array<string, mixed> $attributes
     */

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($model->encrypted && $value !== null) {
            $value = Crypt::encrypt($value);
        }
        return $value;
    }

    // Unit Test testSetEncryptsValueIfRequired failed with validating $model->encrypted
    // This is because the model does not have an 'encrypted' property.
    // To fix this, we check the attributes array for 'encrypted' instead.

    // public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    // {
    //     if (($attributes['encrypted'] ?? false) && $value !== null) {
    //         $value = Crypt::encrypt($value);
    //     }
    //     return $value;
    // }
}
