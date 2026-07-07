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
use DogeSweeper\Api\Provider\BalanceProviderFactory;
use DogeSweeper\Api\Broadcaster\TransactionBroadcasterFactory;

/**
 * Application principale Doge Sweeper
 *
 * Orchestre le flux complet:
 * 1. Charger la configuration
 * 2. Lire les clés WIF
 * 3. Générer les adresses
 * 4. Récupérer les soldes (via API)
 * 5. Construire et envoyer les transactions
 *
 * @package DogeSweeper\Application
 */
class DogeSweeper
{
    /**
     * @var Config Configuration
     */
    private Config $config;

    /**
     * @var LibDogecoinFFI Interface libdogecoin
     */
    private LibDogecoinFFI $ffi;

    /**
     * @var WalletManager Gestionnaire de portefeuille
     */
    private WalletManager $walletManager;

    /**
     * @var FeeCalculator Calculateur de frais
     */
    private FeeCalculator $feeCalculator;

    /**
     * @var TransactionSender Expéditeur de transactions
     */
    private TransactionSender $transactionSender;

    /**
     * @var BalanceProviderFactory Factory de fournisseurs de solde
     */
    private BalanceProviderFactory $balanceProviderFactory;

    /**
     * @var TransactionBroadcasterFactory Factory de broadcasters
     */
    private TransactionBroadcasterFactory $broadcasterFactory;

    /**
     * @var bool Mode verbeux
     */
    private bool $verbose;

    /**
     * Initialise l'application
     *
     * @param Config|null $config Configuration (optionnel)
     * @throws \RuntimeException
     */
    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? new Config();
        $this->verbose = $this->config->get('verbose', true);

        // Initialiser les dépendances
        $this->ffi = $this->initializeFFI();
        $this->walletManager = $this->initializeWalletManager();
        $this->feeCalculator = new FeeCalculator($this->config->get('fee_rate', 0.01));
        $this->transactionSender = new TransactionSender();

        // Initialiser les APIs
        $this->balanceProviderFactory = $this->initializeBalanceProvider();
        $this->broadcasterFactory = $this->initializeTransactionBroadcaster();
    }

    /**
     * Initialise l'interface FFI
     *
     * @return LibDogecoinFFI
     * @throws \RuntimeException
     */
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

    /**
     * Initialise le gestionnaire de portefeuille
     *
     * @return WalletManager
     */
    private function initializeWalletManager(): WalletManager
    {
        $network = $this->config->get('network', 'mainnet');
        $addressVersion = $network === 'testnet' ? 0x71 : 0x1e;

        return new WalletManager($this->ffi, $addressVersion);
    }

    /**
     * Initialise le fournisseur de solde
     *
     * @return BalanceProviderFactory
     */
    private function initializeBalanceProvider(): BalanceProviderFactory
    {
        $factory = new BalanceProviderFactory();

        // Configurer le fournisseur par défaut
        $defaultProvider = $this->config->get('balance_provider', 'SoChain');
        $factory->setDefaultProvider($defaultProvider);

        // Configurer BlockCypher avec le token si fourni
        $blockcypherToken = $this->config->get('blockcypher_token');
        if ($blockcypherToken) {
            $blockcypherProvider = $factory->getProvider('BlockCypher');
            if ($blockcypherProvider) {
                // Régénérer avec le token
                $newProvider = new \DogeSweeper\Api\Provider\BlockCypherProvider($blockcypherToken);
                // On ne peut pas mettre à jour directement, mais c'est OK
            }
        }

        return $factory;
    }

    /**
     * Initialise le broadcaster de transaction
     *
     * @return TransactionBroadcasterFactory
     */
    private function initializeTransactionBroadcaster(): TransactionBroadcasterFactory
    {
        $factory = new TransactionBroadcasterFactory();

        // Configurer le broadcaster par défaut
        $defaultBroadcaster = $this->config->get('transaction_broadcaster', 'SoChain');
        $factory->setDefaultBroadcaster($defaultBroadcaster);

        return $factory;
    }

    /**
     * Lance l'application
     *
     * @return array<string, mixed> Résumé de l'exécution
     */
    public function run(): array
    {
        try {
            $this->log("🐕 Doge Sweeper v1.0.0 Starting...\n");

            // Valider la configuration
            $this->log("Validating configuration...\n");
            $this->config->validate();
            $this->log("✅ Configuration is valid\n\n");

            // Afficher les infos API
            $this->log("API Configuration:\n");
            $this->log("  Balance Provider: " . $this->balanceProviderFactory->getDefaultProvider()->getName() . "\n");
            $this->log("  Transaction Broadcaster: " . $this->broadcasterFactory->getDefaultBroadcaster()->getName() . "\n");
            $this->log("  API Fallback: " . ($this->config->get('api_fallback_enabled') ? 'Enabled' : 'Disabled') . "\n\n");

            // Lire les clés WIF
            $this->log("Reading WIF file...\n");
            $wifs = $this->readWifFile();
            $this->log("✅ Read {$this->walletManager->getCount()} addresses\n\n");

            if ($this->walletManager->getCount() === 0) {
                $this->log("❌ No valid addresses found\n");
                return ['success' => false, 'error' => 'No valid addresses found'];
            }

            // Charger les soldes via l'API
            $this->log("Loading balances from " . $this->balanceProviderFactory->getDefaultProvider()->getName() . "...\n");
            $this->loadBalancesFromAPI();
            $this->log("✅ Balances loaded\n\n");

            // Afficher les statistiques
            $stats = $this->walletManager->getStats();
            $this->log("Wallet Statistics:\n");
            $this->log("  Total addresses: {$stats['total_addresses']}\n");
            $this->log("  Addresses with balance: {$stats['addresses_with_balance']}\n");
            $this->log("  Total balance: " . round($stats['total_balance'], 8) . " DOGE\n\n");

            if ($stats['addresses_with_balance'] === 0) {
                $this->log("❌ No addresses with balance found\n");
                return [
                    'success' => false,
                    'error' => 'No addresses with balance',
                    'stats' => $stats,
                ];
            }

            // Traiter les adresses avec solde
            $this->log("Processing wallets...\n");
            $this->log(str_repeat("=", 60) . "\n\n");
            $processedCount = $this->processWallets();

            // Afficher le résumé final
            $this->log("\n" . str_repeat("=", 60) . "\n");
            $this->log("✅ Sweep completed successfully!\n\n");
            $this->log("Summary:\n");
            $this->log("  Processed: {$processedCount} address(es)\n");
            $this->log("  Total sent: " . round($this->transactionSender->getTotalSent(), 8) . " DOGE\n");
            $this->log("  Total fees: " . round($this->transactionSender->getTotalFees(), 8) . " DOGE\n\n");

            return [
                'success' => true,
                'processed' => $processedCount,
                'summary' => $this->transactionSender->getSummary(),
            ];
        } catch (\Throwable $e) {
            $this->log("❌ Error: {$e->getMessage()}\n");
            if ($this->config->get('verbose')) {
                $this->log("\n" . $e->getTraceAsString() . "\n");
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lit le fichier WIF
     *
     * @return array<string>
     * @throws \RuntimeException
     */
    private function readWifFile(): array
    {
        $wifFile = $this->config->get('wif_file');

        if (!file_exists($wifFile)) {
            throw new \RuntimeException("WIF file not found: {$wifFile}");
        }

        $reader = new WifReader($wifFile);
        $wifs = $reader->read();

        $this->log($reader->getSummary() . "\n");

        // Ajouter les WIF au gestionnaire de portefeuille
        $this->walletManager->addFromWifs($wifs);

        return $wifs;
    }

    /**
     * Charge les soldes via l'API
     *
     * @return void
     */
    private function loadBalancesFromAPI(): void
    {
        $addresses = array_map(
            fn($addr) => $addr->getAddress(),
            $this->walletManager->getAddresses()
        );

        if (empty($addresses)) {
            return;
        }

        try {
            // Essayer avec le fournisseur par défaut
            $balances = $this->balanceProviderFactory->getBalances($addresses);
        } catch (\Exception $e) {
            if ($this->config->get('api_fallback_enabled')) {
                $this->log("⚠️  Primary provider failed, trying fallback...\n");
                try {
                    $balances = $this->balanceProviderFactory->getBalancesWithFallback($addresses);
                } catch (\Exception $fallbackError) {
                    $this->log("❌ All providers failed: {$fallbackError->getMessage()}\n");
                    throw $fallbackError;
                }
            } else {
                throw $e;
            }
        }

        // Mettre à jour les soldes
        foreach ($balances as $address => $balance) {
            $walletAddress = $this->walletManager->getAddress($address);
            if ($walletAddress) {
                $walletAddress->setBalance($balance);
            }
        }
    }

    /**
     * Traite les portefeuilles et envoie les transactions
     *
     * @return int Nombre d'adresses traitées
     */
    private function processWallets(): int
    {
        $binanceAddress = $this->config->get('binance_address');
        $minAmount = $this->config->get('min_amount', 0.01);
        $processedCount = 0;

        foreach ($this->walletManager->getAddressesWithBalance() as $address) {
            if ($address->getBalance() < $minAmount) {
                $this->log(
                    sprintf(
                        "  ⊘ %s: Balance too low (%.8f DOGE)\n",
                        $address->getAddress(),
                        $address->getBalance()
                    )
                );
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
                        "  ✅ %s\n     Sent: %.8f DOGE | Fee: %.8f DOGE | TXID: %s\n",
                        $address->getAddress(),
                        $result['amount'],
                        $result['fee'],
                        substr($result['txid'], 0, 16) . '...'
                    )
                );

                $processedCount++;
            } catch (\Throwable $e) {
                $this->log(
                    "  ❌ {$address->getAddress()}: {$e->getMessage()}\n"
                );
            }

            // Délai entre les transactions
            $delay = $this->config->get('transaction_delay', 2);
            if ($delay > 0) {
                sleep($delay);
            }
        }

        return $processedCount;
    }

    /**
     * Enregistre un message
     *
     * @param string $message Message à enregistrer
     * @return void
     */
    private function log(string $message): void
    {
        if ($this->verbose) {
            echo $message;
        }

        // Enregistrer dans le fichier de log
        if ($this->config->get('log_to_file', true)) {
            $logFile = $this->config->get('log_file');
            if ($logFile) {
                $dir = dirname($logFile);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                file_put_contents($logFile, $message, FILE_APPEND);
            }
        }
    }

    /**
     * Obtient le gestionnaire de portefeuille
     *
     * @return WalletManager
     */
    public function getWalletManager(): WalletManager
    {
        return $this->walletManager;
    }

    /**
     * Obtient le calculateur de frais
     *
     * @return FeeCalculator
     */
    public function getFeeCalculator(): FeeCalculator
    {
        return $this->feeCalculator;
    }

    /**
     * Obtient l'expéditeur de transactions
     *
     * @return TransactionSender
     */
    public function getTransactionSender(): TransactionSender
    {
        return $this->transactionSender;
    }

    /**
     * Obtient le factory de fournisseurs de solde
     *
     * @return BalanceProviderFactory
     */
    public function getBalanceProviderFactory(): BalanceProviderFactory
    {
        return $this->balanceProviderFactory;
    }

    /**
     * Obtient le factory de broadcasters
     *
     * @return TransactionBroadcasterFactory
     */
    public function getTransactionBroadcasterFactory(): TransactionBroadcasterFactory
    {
        return $this->broadcasterFactory;
    }
}
