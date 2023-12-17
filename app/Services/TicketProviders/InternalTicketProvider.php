<?php
namespace App\Services\TicketProviders;

class InternalTicketProvider extends AbstractTicketProvider
{
    protected string $code = 'internal';
    protected string $name = 'Internal';

    public function configMapping(): array
    {
        return [];
    }
}
