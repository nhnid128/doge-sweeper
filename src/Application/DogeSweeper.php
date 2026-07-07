<?php

declare(strict_types=1);

namespace DogeSweeper\Application;

use DogeSweeper\Config\Config;
use DogeSweeper\Integration\LibDogecoinFFI;
use DogeSweeper\Wallet\WifReader;
use DogeSweeper\Wallet\WalletManager;
use DogeSweeper\Transaction\FeeCalculator;
use DogeSweeper\Transaction\TransactionBuilder;
use DogeSweeper\Transaction\TransactionSender;

/**
 * Application principale Doge Sweeper
 *
 * @package DogeSweeper\Application
 */
class DogeSweeper
{
    private Config $config;
    private LibDogecoinFFI $ffi;
    private WalletManager $walletManager;
    private FeeCalculator $feeCalculator;
    private TransactionSender $transactionSender;
    private bool $verbose;

    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? new Config();
        $this->verbose = $this->config->get('verbose', true);

        $this->ffi = $this->initializeFFI();
        $this->walletManager = $this->initializeWalletManager();
        $this->feeCalculator = new FeeCalculator($this->config->get('fee_rate', 0.01));
        $this->transactionSender = new TransactionSender();
    }

    private function initializeFFI(): LibDogecoinFFI
    {
        try {
            return new LibDogecoinFFI();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed to initialize libdogecoin: " . $e->getMessage()
            );
        }
    }

    private function initializeWalletManager(): WalletManager
    {
        $network = $this->config->get('network', 'mainnet');
        $addressVersion = $network === 'testnet' ? 0x71 : 0x1e;
        return new WalletManager($this->ffi, $addressVersion);
    }

    public function run(): array
    {
        try {
            $this->log("🐕 Doge Sweeper v1.0.0 Starting...\n");

            $this->log("Validating configuration...");
            $this->config->validate();
            $this->log("✅ Configuration is valid\n");

            $this->log("Reading WIF file...");
            $wifs = $this->readWifFile();
            $this->log("✅ Read {$this->walletManager->getCount()} addresses\n");

            if ($this->walletManager->getCount() === 0) {
                $this->log("❌ No valid addresses found\n");
                return ['success' => false, 'error' => 'No valid addresses found'];
            }

            $this->log("Loading balances...");
            $this->simulateLoadBalances();
            $this->log("✅ Balances loaded\n");

            $this->log("Processing wallets...\n");
            $processedCount = $this->processWallets();

            $this->log("\n" . str_repeat("=", 60) . "\n");
            $this->log("✅ Sweep completed successfully!\n");
            $this->log("  Processed: {$processedCount} address(es)\n");
            $this->log("  Total sent: " . round($this->transactionSender->getTotalSent(), 8) . " DOGE\n");
            $this->log("  Total fees: " . round($this->transactionSender->getTotalFees(), 8) . " DOGE\n");

            return [
                'success' => true,
                'processed' => $processedCount,
                'summary' => $this->transactionSender->getSummary(),
            ];
        } catch (\Throwable $e) {
            $this->log("❌ Error: {$e->getMessage()}\n");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function readWifFile(): array
    {
        $wifFile = $this->config->get('wif_file');

        if (!file_exists($wifFile)) {
            throw new \RuntimeException("WIF file not found: {$wifFile}");
        }

        $reader = new WifReader($wifFile);
        $wifs = $reader->read();

        $this->log($reader->getSummary());
        $this->walletManager->addFromWifs($wifs);

        return $wifs;
    }

    private function simulateLoadBalances(): void
    {
        foreach ($this->walletManager->getAddresses() as $address) {
            $balance = rand(0, 1000) / 100;
            $address->setBalance($balance);
        }
    }

    private function processWallets(): int
    {
        $binanceAddress = $this->config->get('binance_address');
        $minAmount = $this->config->get('min_amount', 0.01);
        $processedCount = 0;

        foreach ($this->walletManager->getAddressesWithBalance() as $address) {
            if ($address->getBalance() < $minAmount) {
                $this->log("  ⊘ {$address->getAddress()}: Balance too low ({$address->getBalance()} DOGE)\n");
                continue;
            }

            try {
                $builder = new TransactionBuilder($address, $binanceAddress, $this->feeCalculator);
                $builder->setMaxAmount();
                $builder->validate();

                $tx = $builder->build();
                $result = $this->transactionSender->send($tx);

                $this->log(
                    sprintf(
                        "  ✅ %s: Sent %.8f DOGE (fee: %.8f DOGE, txid: %s)\n",
                        $address->getAddress(),
                        $result['amount'],
                        $result['fee'],
                        substr($result['txid'], 0, 8) . '...'
                    )
                );

                $processedCount++;
            } catch (\Throwable $e) {
                $this->log(
                    "  ❌ {$address->getAddress()}: {$e->getMessage()}\n"
                );
            }

            $delay = $this->config->get('transaction_delay', 2);
            if ($delay > 0) {
                sleep($delay);
            }
        }

        return $processedCount;
    }

    private function log(string $message): void
    {
        if ($this->verbose) {
            echo $message;
        }

        $logFile = $this->config->get('log_file');
        if ($logFile) {
            file_put_contents($logFile, $message, FILE_APPEND);
        }
    }

    public function getWalletManager(): WalletManager
    {
        return $this->walletManager;
    }

    public function getFeeCalculator(): FeeCalculator
    {
        return $this->feeCalculator;
    }

    public function getTransactionSender(): TransactionSender
    {
        return $this->transactionSender;
    }
}
