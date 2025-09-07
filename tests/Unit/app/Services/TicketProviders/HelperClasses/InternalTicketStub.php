<?php

namespace Tests\Unit\app\Services\TicketProviders\HelperClasses;

class InternalTicketStub
{
    public $external_id;
    public $user = null;
    public $deleted = false;
    public $saved = false;

    public function __construct($external_id)
    {
        $this->external_id = $external_id;
    }

    public function __toString()
    {
        return 'Ticket#' . $this->external_id;
    }

    public function delete()
    {
        $this->deleted = true;
    }

    public function user()
    {
        $parent = $this;
        return new class ($parent) {
            private $parent;

            public function __construct($parent)
            {
                $this->parent = $parent;
            }

            public function associate($user)
            {
                $this->parent->user = $user;
            }
        };
    }

    public function save()
    {
        $this->saved = true;
    }
}
