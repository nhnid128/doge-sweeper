<?php

declare(strict_types=1);

namespace DogeSweeper\Exception;

/**
 * Exception levée quand une clé WIF est invalide
 *
 * @package DogeSweeper\Exception
 */
class InvalidWifException extends \Exception
{
    public function __construct(string $wif, string $reason = '')
    {
        $message = "Invalid WIF: {$wif}";
        if ($reason) {
            $message .= " ({$reason})";
        }
        parent::__construct($message);
    }
}
