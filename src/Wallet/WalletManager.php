<?php

declare(strict_types=1);

namespace DogeSweeper\Wallet;

use DogeSweeper\Integration\LibDogecoinFFI;
use DogeSweeper\Exception\InvalidWifException;

/**
 * Gestionnaire de portefeuille
 *
 * @package DogeSweeper\Wallet
 */
class WalletManager
{
    private LibDogecoinFFI $ffi;
    private array $addresses = [];
    private int $addressVersion;

    public function __construct(LibDogecoinFFI $ffi, int $addressVersion = 0x1e)
    {
        $this->ffi = $ffi;
        $this->addressVersion = $addressVersion;
    }

    public function addFromWif(string $wif): WalletAddress
    {
        try {
            if (!$this->ffi->isValidWif($wif)) {
                throw new InvalidWifException($wif, 'Invalid WIF');
            }

            $privkey = $this->ffi->wifToPrivkey($wif);
            $address = $this->ffi->addressFromWif($wif, $this->addressVersion);

            $walletAddress = new WalletAddress($address, $wif, $privkey);
            $this->addresses[$address] = $walletAddress;

            return $walletAddress;
        } catch (\Throwable $e) {
            throw new InvalidWifException($wif, $e->getMessage());
        }
    }

    public function addFromWifs(array $wifs): array
    {
        $added = [];
        $errors = [];

        foreach ($wifs as $index => $wif) {
            try {
                $added[] = $this->addFromWif($wif);
            } catch (InvalidWifException $e) {
                $errors[$index] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            echo "Erreurs lors du traitement des clés WIF:\n";
            foreach ($errors as $index => $error) {
                echo "  Index {$index}: {$error}\n";
            }
        }

        return $added;
    }

    public function getAddresses(): array
    {
        return array_values($this->addresses);
    }

    public function getAddress(string $address): ?WalletAddress
    {
        return $this->addresses[$address] ?? null;
    }

    public function getAddressesWithBalance(): array
    {
        return array_filter($this->addresses, fn(WalletAddress $addr) => $addr->hasBalance());
    }

    public function getCount(): int
    {
        return count($this->addresses);
    }

    public function getCountWithBalance(): int
    {
        return count($this->getAddressesWithBalance());
    }

    public function getTotalBalance(): float
    {
        return array_reduce(
            $this->addresses,
            fn(float $carry, WalletAddress $addr) => $carry + $addr->getBalance(),
            0.0
        );
    }

    public function getStats(): array
    {
        return [
            'total_addresses' => $this->getCount(),
            'addresses_with_balance' => $this->getCountWithBalance(),
            'total_balance' => $this->getTotalBalance(),
            'avg_balance' => $this->getCount() > 0 ? $this->getTotalBalance() / $this->getCount() : 0,
        ];
    }

    public function getSummary(): string
    {
        $stats = $this->getStats();
        $summary = "Wallet Summary:\n";
        $summary .= "  Total addresses: {$stats['total_addresses']}\n";
        $summary .= "  Addresses with balance: {$stats['addresses_with_balance']}\n";
        $summary .= "  Total balance: {$stats['total_balance']} DOGE\n";
        $summary .= "  Average balance: {$stats['avg_balance']} DOGE\n";

        return $summary;
    }
}
