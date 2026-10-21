<?php

namespace Tests\Unit\app\Http\Controllers\Admin\HelperClasses;

use Illuminate\Http\Request;
use Tests\Unit\app\Http\Controllers\Admin\HelperClasses;

class FakeRequest extends Request
{
    protected $sess;

    public function __construct()
    {
        parent::__construct();
        $this->sess = new HelperClasses\FakeSession();
    }

    public function session()
    {
        return $this->sess;
    }
}
