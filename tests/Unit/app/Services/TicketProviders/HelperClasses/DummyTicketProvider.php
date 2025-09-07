<?php

namespace Tests\Unit\app\Services\TicketProviders\HelperClasses;

use App\Services\TicketProviders\AbstractTicketProvider;

class DummyTicketProvider extends AbstractTicketProvider
{
    protected string $name = 'Dummy Provider';
    protected string $code = 'dummy';

    public function __construct($provider = null)
    {
        parent::__construct($provider);
    }
}
