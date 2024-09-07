<?php

namespace App\Services;

use App\Contracts\AbstractDoor;
use App\Contracts\AbstractTable;
use App\Contracts\ProductFactory;

class ConcreteProductFactory implements ProductFactory
{
    public function createDoor(): AbstractDoor
    {
        return new ConcreteDoor();
    }

    public function createTable(): AbstractTable
    {
        return new ConcreteTable();
    }
}
