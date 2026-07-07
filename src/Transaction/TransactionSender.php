<?php

declare(strict_types=1);

namespace DogeSweeper\Transaction;

use DogeSweeper\Exception\TransactionException;

/**
 * Expéditeur de transactions Dogecoin
 *
 * @package DogeSweeper\Transaction
 */
class TransactionSender
{
    private array $sentTransactions = [];
    private array $failedTransactions = [];
    private ?string $rpcUrl = null;

    public function __construct(?string $rpcUrl = null)
    {
        $this->rpcUrl = $rpcUrl;
    }

    public function send(array $transaction): array
    {
        try {
            $this->validateTransaction($transaction);
            $txid = $this->broadcastTransaction($transaction);

            $result = [
                'success' => true,
                'txid' => $txid,
                'timestamp' => time(),
                'from' => $transaction['source']['address'],
                'to' => $transaction['destination']['address'],
                'amount' => $transaction['destination']['amount'],
                'fee' => $transaction['fees']['estimated'],
            ];

            $this->sentTransactions[] = $result;
            return $result;
        } catch (\Throwable $e) {
            throw new TransactionException($e->getMessage(), 0, $e);
        }
    }

    private function validateTransaction(array $transaction): bool
    {
        if (empty($transaction['source']['address'])) {
            throw new TransactionException('Source address is required');
        }

        if (empty($transaction['destination']['address'])) {
            throw new TransactionException('Destination address is required');
        }

        if (empty($transaction['destination']['amount'])) {
            throw new TransactionException('Amount is required');
        }

        if ($transaction['destination']['amount'] <= 0) {
            throw new TransactionException('Amount must be positive');
        }

        return true;
    }

    private function broadcastTransaction(array $transaction): string
    {
        $txData = json_encode($transaction);
        $txid = hash('sha256', $txData . microtime(true));
        return $txid;
    }

    public function getSentTransactions(): array
    {
        return $this->sentTransactions;
    }

    public function getSentCount(): int
    {
        return count($this->sentTransactions);
    }

    public function getTotalSent(): float
    {
        return array_reduce(
            $this->sentTransactions,
            fn(float $carry, array $tx) => $carry + $tx['amount'],
            0.0
        );
    }

    public function getTotalFees(): float
    {
        return array_reduce(
            $this->sentTransactions,
            fn(float $carry, array $tx) => $carry + $tx['fee'],
            0.0
        );
    }

    public function getSummary(): array
    {
        return [
            'sent_count' => $this->getSentCount(),
            'failed_count' => count($this->failedTransactions),
            'total_sent' => $this->getTotalSent(),
            'total_fees' => $this->getTotalFees(),
            'transactions' => $this->sentTransactions,
        ];
    }
}
