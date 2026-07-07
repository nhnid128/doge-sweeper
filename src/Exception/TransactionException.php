<?php

declare(strict_types=1);

namespace DogeSweeper\Exception;

/**
 * Exception levée lors d'une erreur de transaction
 *
 * @package DogeSweeper\Exception
 */
class TransactionException extends \Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Transaction error: {$message}", $code, $previous);
    }
}
