<?php

declare(strict_types=1);

namespace DogeSweeper\Wallet;

/**
 * Représente une adresse Dogecoin avec ses informations associées
 *
 * @package DogeSweeper\Wallet
 */
class WalletAddress
{
    public function __construct(
        private string $address,
        private string $wif,
        private string $privkey,
        private float $balance = 0.0
    ) {
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getWif(): string
    {
        return $this->wif;
    }

    public function getPrivkey(): string
    {
        return $this->privkey;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }

    public function hasBalance(): bool
    {
        return $this->balance > 0;
    }

    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'balance' => $this->balance,
            'has_balance' => $this->hasBalance(),
        ];
    }

    public function __toString(): string
    {
        return $this->address;
    }
}
