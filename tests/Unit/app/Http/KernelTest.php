<?php

namespace Tests\Unit\app\Http;

use Tests\TestCase;
use App\Http\Kernel;

class KernelTest extends TestCase
{
    public function testCanInstantiateKernel()
    {
        $kernel = new Kernel(app(), app('router'));
        $this->assertInstanceOf(Kernel::class, $kernel);
    }
}
