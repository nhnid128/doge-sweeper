<?php

declare(strict_types=1);

namespace DogeSweeper\Transaction;

/**
 * Calculateur de frais de transaction
 *
 * @package DogeSweeper\Transaction
 */
class FeeCalculator
{
    private const MIN_TX_SIZE = 192;
    private const INPUT_SIZE = 148;
    private const OUTPUT_SIZE = 34;
    private const FIXED_SIZE = 10;

    public function __construct(private float $feeRate = 0.01)
    {
    }

    public function getFeeRate(): float
    {
        return $this->feeRate;
    }

    public function setFeeRate(float $feeRate): void
    {
        if ($feeRate <= 0) {
            throw new \InvalidArgumentException('Fee rate must be positive');
        }
        $this->feeRate = $feeRate;
    }

    public function estimateTxSize(int $inputCount, int $outputCount = 1): int
    {
        $size = self::FIXED_SIZE +
                ($inputCount * self::INPUT_SIZE) +
                ($outputCount * self::OUTPUT_SIZE);

        return max($size, self::MIN_TX_SIZE);
    }

    public function calculateFee(int $inputCount, int $outputCount = 1): float
    {
        $txSize = $this->estimateTxSize($inputCount, $outputCount);
        $sizeInKb = $txSize / 1024;
        $fee = $sizeInKb * $this->feeRate;

        return round($fee, 8);
    }

    public function calculateMaxAmount(float $balance, int $inputCount, int $outputCount = 1): float
    {
        $fee = $this->calculateFee($inputCount, $outputCount);
        $maxAmount = $balance - $fee;

        if ($maxAmount <= 0) {
            return 0;
        }

        return round($maxAmount, 8);
    }

    public function getSummary(float $balance, int $inputCount = 1): array
    {
        $txSize = $this->estimateTxSize($inputCount);
        $fee = $this->calculateFee($inputCount);
        $maxAmount = $this->calculateMaxAmount($balance, $inputCount);

        return [
            'balance' => $balance,
            'estimated_tx_size' => $txSize,
            'estimated_tx_size_kb' => round($txSize / 1024, 4),
            'fee_rate' => $this->feeRate,
            'estimated_fee' => $fee,
            'max_amount_to_send' => $maxAmount,
            'fee_percentage' => $balance > 0 ? round(($fee / $balance) * 100, 2) : 0,
        ];
    }
}
