<?php

namespace Tests\Unit\app\Transformers\V1\HelperClasses;

use Illuminate\Support\Carbon;

class DummyObject
{
    public $id = 1;
    public $created_at;
    public $updated_at;

    public function __construct()
    {
        $this->created_at = Carbon::parse('2022-01-01T00:00:00Z');
        $this->updated_at = Carbon::parse('2022-01-02T00:00:00Z');
    }
}
