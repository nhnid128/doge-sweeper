<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Provider;

use DogeSweeper\Api\BalanceProviderInterface;

/**
 * Fournisseur dogechain.info pour Dogecoin
 *
 * API Documentation: https://dogechain.info/api
 * - Limite gratuite: Pas de limite connue
 * - Très simple et rapide
 * - Maintenu par la communauté Dogecoin
 *
 * @package DogeSweeper\Api\Provider
 */
class DogechainProvider implements BalanceProviderInterface
{
    /**
     * URL API dogechain.info
     */
    private const BASE_URL = 'https://dogechain.info/api/v1';

    /**
     * Délai entre les requêtes (en secondes)
     */
    private float $requestDelay = 0.1;

    /**
     * Récupère le solde d'une adresse
     *
     * @param string $address Adresse Dogecoin
     * @return float Solde en DOGE
     */
    public function getBalance(string $address): float
    {
        $data = $this->request("/address/balance/{$address}");

        // dogechain.info retourne le solde en DOGE directement
        return floatval($data);
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
     * Récupère les informations d'une adresse
     *
     * @param string $address Adresse Dogecoin
     * @return array<string, mixed> Informations de l'adresse
     */
    public function getAddressInfo(string $address): array
    {
        return $this->request("/address/{$address}");
    }

    /**
     * Récupère les UTXOs d'une adresse
     *
     * @param string $address Adresse Dogecoin
     * @return array<array> Liste des UTXOs
     */
    public function getUtxos(string $address): array
    {
        $info = $this->getAddressInfo($address);
        return $info['txs'] ?? [];
    }

    /**
     * Obtient le nom du fournisseur
     *
     * @return string
     */
    public function getName(): string
    {
        return 'dogechain.info';
    }

    /**
     * Vérifie si le fournisseur est disponible
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            // Vérifier avec une adresse connue
            $this->getBalance('A8V8qKmBeUiYjHgr73NvcQcNHnK74MRZPa');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Effectue une requête HTTP GET
     *
     * @param string $endpoint Point de terminaison API
     * @return mixed Réponse JSON ou string
     * @throws \RuntimeException
     */
    private function request(string $endpoint): mixed
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
                "dogechain.info API request failed for {$endpoint}"
            );
        }

        // Essayer de décoder comme JSON
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        // Vérifier les erreurs API
        if (is_array($data) && isset($data['error'])) {
            throw new \RuntimeException(
                "dogechain.info API error: {$data['error']}"
            );
        }

        return $data ?? $response;
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
