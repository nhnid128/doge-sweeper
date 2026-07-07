<?php

declare(strict_types=1);

namespace DogeSweeper\Transaction;

use DogeSweeper\Wallet\WalletAddress;
use DogeSweeper\Exception\InsufficientFundsException;

/**
 * Constructeur de transaction Dogecoin
 *
 * @package DogeSweeper\Transaction
 */
class TransactionBuilder
{
    private WalletAddress $sourceAddress;
    private string $destinationAddress;
    private float $amount = 0;
    private FeeCalculator $feeCalculator;

    public function __construct(
        WalletAddress $sourceAddress,
        string $destinationAddress,
        FeeCalculator $feeCalculator
    ) {
        $this->sourceAddress = $sourceAddress;
        $this->destinationAddress = $destinationAddress;
        $this->feeCalculator = $feeCalculator;
    }

    public function setAmount(float $amount): self
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        $this->amount = $amount;
        return $this;
    }

    public function setMaxAmount(): self
    {
        $maxAmount = $this->feeCalculator->calculateMaxAmount(
            $this->sourceAddress->getBalance(),
            1
        );

        if ($maxAmount <= 0) {
            throw new InsufficientFundsException(
                $this->sourceAddress->getBalance(),
                $this->feeCalculator->calculateFee(1)
            );
        }

        $this->amount = $maxAmount;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getEstimatedFee(): float
    {
        return $this->feeCalculator->calculateFee(1);
    }

    public function validate(): bool
    {
        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('Amount must be set');
        }

        $totalCost = $this->amount + $this->getEstimatedFee();

        if ($totalCost > $this->sourceAddress->getBalance()) {
            throw new InsufficientFundsException(
                $this->sourceAddress->getBalance(),
                $totalCost
            );
        }

        return true;
    }

    public function getSummary(): array
    {
        return [
            'source_address' => $this->sourceAddress->getAddress(),
            'destination_address' => $this->destinationAddress,
            'amount' => $this->amount,
            'estimated_fee' => $this->getEstimatedFee(),
            'total_cost' => $this->amount + $this->getEstimatedFee(),
            'source_balance' => $this->sourceAddress->getBalance(),
            'remaining_balance' => $this->sourceAddress->getBalance() - ($this->amount + $this->getEstimatedFee()),
        ];
    }

    public function build(): array
    {
        $this->validate();

        return [
            'source' => [
                'address' => $this->sourceAddress->getAddress(),
                'privkey' => $this->sourceAddress->getPrivkey(),
                'balance' => $this->sourceAddress->getBalance(),
            ],
            'destination' => [
                'address' => $this->destinationAddress,
                'amount' => $this->amount,
            ],
            'fees' => [
                'rate' => $this->feeCalculator->getFeeRate(),
                'estimated' => $this->getEstimatedFee(),
            ],
        ];
    }
}
