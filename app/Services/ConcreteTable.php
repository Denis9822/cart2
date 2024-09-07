<?php

namespace App\Services;

use App\Contracts\AbstractTable;

class ConcreteTable implements AbstractTable
{

    public function make(): string
    {
        return "concrete table";
    }
}
