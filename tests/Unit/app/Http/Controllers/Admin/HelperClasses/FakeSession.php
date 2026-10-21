<?php

namespace Tests\Unit\app\Http\Controllers\Admin\HelperClasses;

class FakeSession
{
    public $data = [];

    public function flush()
    {
        $this->data = [];
    }

    public function regenerate($a = true)
    {
        /* noop */
    }

    public function put($k, $v)
    {
        $this->data[$k] = $v;
    }

    public function get($k, $default = null)
    {
        return $this->data[$k] ?? $default;
    }
}
