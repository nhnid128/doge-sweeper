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
    // Configuration de BlockCypher
    // ============================================
    // Token API BlockCypher (optionnel, gratuit 200 req/h)
    // Voir: https://www.blockcypher.com/dev/dogecoin/
    'blockcypher_token' => '',

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
