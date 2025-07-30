<?php

namespace Tests\Unit\app\Console;

use Tests\TestCase;
use App\Console\Kernel;

class KernelTest extends TestCase
{
    public function testCanInstantiateKernel()
    {
        $kernel = new Kernel(app(), app('events'));
        $this->assertInstanceOf(Kernel::class, $kernel);
    }
}
