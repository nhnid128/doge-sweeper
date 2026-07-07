<?php
/**
 * Configuration par défaut de Doge Sweeper
 *
 * @package DogeSweeper\Config
 */

return [
    // Adresse Binance cible pour les transferts
    'binance_address' => '',

    // Taux de frais en DOGE/KB
    'fee_rate' => 0.01,

    // Réseau: 'mainnet' ou 'testnet'
    'network' => 'mainnet',

    // Chemin du fichier WIF
    'wif_file' => __DIR__ . '/../data/wif.txt',

    // Délai d'attente entre les transactions (en secondes)
    'transaction_delay' => 2,

    // Confirmation minimale requise
    'min_confirmations' => 1,

    // Activer le mode verbeux
    'verbose' => true,

    // Chemin du fichier de log
    'log_file' => __DIR__ . '/../data/sweep.log',

    // Montant minimum pour traiter (en DOGE)
    'min_amount' => 0.01,
];
