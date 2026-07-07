<?php

declare(strict_types=1);

namespace DogeSweeper\Exception;

/**
 * Exception levée quand les fonds sont insuffisants
 *
 * @package DogeSweeper\Exception
 */
class InsufficientFundsException extends \Exception
{
    public function __construct(float $balance, float $required)
    {
        $message = "Insufficient funds: {$balance} < {$required}";
        parent::__construct($message);
    }
}
