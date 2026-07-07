<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Provider;

use DogeSweeper\Api\BalanceProviderInterface;

/**
 * Fournisseur SoChain pour Dogecoin
 *
 * API Documentation: https://sochain.com/api
 * - Limite gratuite: Gratuit, ~3 requêtes/seconde
 * - Pas de limite d'API key
 * - Très fiable et rapide
 *
 * @package DogeSweeper\Api\Provider
 */
class SoChainProvider implements BalanceProviderInterface
{
    /**
     * URL API SoChain
     */
    private const BASE_URL = 'https://sochain.com/api/v2';

    /**
     * Réseau blockchain
     */
    private string $network;

    /**
     * Délai entre les requêtes (en secondes)
     */
    private float $requestDelay = 0.35;

    /**
     * Initialise le fournisseur SoChain
     *
     * @param string $network Réseau: 'DOGE' (mainnet) ou 'DOGETEST' (testnet)
     */
    public function __construct(string $network = 'DOGE')
    {
        if (!in_array($network, ['DOGE', 'DOGETEST'], true)) {
            throw new \InvalidArgumentException(
                "Invalid network. Must be 'DOGE' or 'DOGETEST'"
            );
        }
        $this->network = $network;
    }

    /**
     * Récupère le solde d'une adresse
     *
     * @param string $address Adresse Dogecoin
     * @return float Solde en DOGE
     */
    public function getBalance(string $address): float
    {
        $data = $this->request("/address/{$this->network}/{$address}");

        $balanceDoge = floatval($data['data']['confirmed_balance'] ?? 0);
        return $balanceDoge;
    }

    /**
     * Récupère les soldes de plusieurs adresses
     *
     * @param array<string> $addresses Adresses Dogecoin
     * @return array<string, float> Mapping address => solde
     */
    public function getBalances(array $addresses): array
    {
        $balances = [];

        foreach ($addresses as $address) {
            try {
                $balances[$address] = $this->getBalance($address);
                // Respecter les limites de taux
                usleep($this->requestDelay * 1e6);
            } catch (\Exception $e) {
                // Si une adresse échoue, retourner 0
                $balances[$address] = 0;
            }
        }

        return $balances;
    }

    /**
     * Récupère les UTXOs d'une adresse
     *
     * @param string $address Adresse Dogecoin
     * @return array<array> Liste des UTXOs
     */
    public function getUtxos(string $address): array
    {
        $data = $this->request("/address/{$this->network}/{$address}?limit=100");

        $utxos = [];
        foreach ($data['data']['txs'] ?? [] as $tx) {
            // Récupérer les outputs non dépensés
            foreach ($tx['outputs'] ?? [] as $output) {
                if ($output['address'] === $address && !$output['is_spent']) {
                    $utxos[] = [
                        'tx_hash' => $tx['txid'],
                        'output_no' => $output['index'],
                        'value' => intval($output['value']),
                        'confirmations' => $tx['confirmations'] ?? 0,
                    ];
                }
            }
        }

        return $utxos;
    }

    /**
     * Obtient le nom du fournisseur
     *
     * @return string
     */
    public function getName(): string
    {
        return 'SoChain';
    }

    /**
     * Vérifie si le fournisseur est disponible
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $this->request('/get_info/' . $this->network);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Effectue une requête HTTP GET
     *
     * @param string $endpoint Point de terminaison API
     * @return array<string, mixed> Réponse JSON
     * @throws \RuntimeException
     */
    private function request(string $endpoint): array
    {
        $url = self::BASE_URL . $endpoint;

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "User-Agent: doge-sweeper\r\n",
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException(
                "SoChain API request failed for {$endpoint}"
            );
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        // Vérifier le statut de la réponse
        if ($data['status'] !== 'success') {
            throw new \RuntimeException(
                "SoChain API error: {$data['status']} - {$data['data']['message'] ?? 'Unknown error'}"
            );
        }

        return $data;
    }

    /**
     * Définit le délai entre les requêtes
     *
     * @param float $delay Délai en secondes
     * @return void
     */
    public function setRequestDelay(float $delay): void
    {
        $this->requestDelay = $delay;
    }
}
