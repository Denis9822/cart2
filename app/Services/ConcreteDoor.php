<?php

namespace App\Services;

use App\Contracts\AbstractDoor;

class ConcreteDoor implements AbstractDoor
{
    public function make(): string
    {
        return 'concrete door';
    }
}
