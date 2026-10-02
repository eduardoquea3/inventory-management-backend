<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly int $available, public readonly int $requested)
    {
        parent::__construct('The requested quantity exceeds available stock.');
    }
}
