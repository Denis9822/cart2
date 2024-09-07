<?php

namespace App\Contracts;

interface ProductFactory
{
    public function createDoor(): AbstractDoor;
    public function createTable(): AbstractTable;
}
