<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Provider;

use DogeSweeper\Api\BalanceProviderInterface;

/**
 * Fournisseur BlockCypher pour Dogecoin
 *
 * API Documentation: https://www.blockcypher.com/dev/dogecoin/
 * - Limite gratuite: 200 requêtes/heure
 * - Supporte les transactions signées
 *
 * @package DogeSweeper\Api\Provider
 */
class BlockCypherProvider implements BalanceProviderInterface
{
    /**
     * URL API BlockCypher
     */
    private const BASE_URL = 'https://api.blockcypher.com/v1/doge/main';

    /**
     * Token API (optionnel, augmente les limites)
     */
    private ?string $token;

    /**
     * Délai entre les requêtes (en secondes)
     */
    private float $requestDelay = 0.1;

    /**
     * Initialise le fournisseur BlockCypher
     *
     * @param string|null $token Token API (optionnel)
     */
    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    /**
     * Récupère le solde d'une adresse
     *
     * @param string $address Adresse Dogecoin
     * @return float Solde en DOGE
     */
    public function getBalance(string $address): float
    {
        $data = $this->request("/addrs/{$address}");

        // BlockCypher retourne le solde en Satoshi (1 DOGE = 10^8 Satoshi)
        $balanceSatoshi = $data['balance'] ?? 0;
        return $balanceSatoshi / 1e8;
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

        // BlockCypher supporte jusqu'à 100 adresses par requête
        $chunks = array_chunk($addresses, 100);

        foreach ($chunks as $chunk) {
            $addressParam = implode(';', $chunk);
            $data = $this->request("/addrs/{$addressParam}");

            foreach ($data['addresses'] ?? [] as $addressData) {
                $address = $addressData['address'];
                $balanceSatoshi = $addressData['balance'] ?? 0;
                $balances[$address] = $balanceSatoshi / 1e8;
            }

            // Respecter les limites de taux
            usleep($this->requestDelay * 1e6);
        }

        return $balances;
    }

    /**
     * Récupère les transactions UTXOs d'une adresse (pour construire une tx)
     *
     * @param string $address Adresse Dogecoin
     * @return array<array> Liste des UTXOs
     */
    public function getUtxos(string $address): array
    {
        $data = $this->request("/addrs/{$address}?unspentOnly=true");
        return $data['txrefs'] ?? [];
    }

    /**
     * Obtient le nom du fournisseur
     *
     * @return string
     */
    public function getName(): string
    {
        return 'BlockCypher';
    }

    /**
     * Vérifie si le fournisseur est disponible
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $this->request('/status');
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

        // Ajouter le token si disponible
        if ($this->token) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'token=' . urlencode($this->token);
        }

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
                "BlockCypher API request failed for {$endpoint}"
            );
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        // Vérifier les erreurs API
        if (isset($data['error'])) {
            throw new \RuntimeException(
                "BlockCypher API error: {$data['error']}"
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
