<?php
/**
 * Configuration par défaut de Doge Sweeper
 *
 * @package DogeSweeper\Config
 */

return [
    // ============================================
    // Configuration Binance
    // ============================================
    'binance_address' => '',

    // ============================================
    // Configuration des frais
    // ============================================
    'fee_rate' => 0.01, // DOGE/KB

    // ============================================
    // Configuration du réseau
    // ============================================
    'network' => 'mainnet', // 'mainnet' ou 'testnet'

    // ============================================
    // Configuration des APIs
    // ============================================

    // Fournisseur de solde par défaut
    // Options: 'SoChain', 'dogechain.info', 'BlockCypher'
    'balance_provider' => 'SoChain',

    // Token API BlockCypher (optionnel, augmente les limites)
    'blockcypher_token' => '',

    // Broadcaster de transaction par défaut
    // Options: 'SoChain', 'dogechain.info', 'BlockCypher'
    'transaction_broadcaster' => 'SoChain',

    // Mode fallback: basculer automatiquement si un API échoue
    'api_fallback_enabled' => true,

    // ============================================
    // Configuration des fichiers
    // ============================================
    'wif_file' => __DIR__ . '/../data/wif.txt',
    'log_file' => __DIR__ . '/../data/sweep.log',

    // ============================================
    // Configuration des transactions
    // ============================================
    'transaction_delay' => 2,        // Délai entre les transactions (en secondes)
    'min_confirmations' => 1,        // Confirmations minimales requises
    'min_amount' => 0.01,            // Montant minimum pour traiter (en DOGE)

    // ============================================
    // Configuration du logging
    // ============================================
    'verbose' => true,              // Afficher les logs en console
    'log_to_file' => true,          // Écrire les logs dans un fichier
];
