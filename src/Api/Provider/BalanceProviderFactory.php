<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Provider;

use DogeSweeper\Api\BalanceProviderInterface;

/**
 * Factory pour le fournisseur BlockCypher
 *
 * Gestion simplifiée avec BlockCypher comme seul fournisseur
 *
 * @package DogeSweeper\Api\Provider
 */
class BalanceProviderFactory
{
    /**
     * @var BlockCypherProvider Instance du fournisseur
     */
    private BlockCypherProvider $provider;

    /**
     * Initialise le factory avec BlockCypher
     *
     * @param string|null $token Token API BlockCypher (optionnel)
     */
    public function __construct(?string $token = null)
    {
        $this->provider = new BlockCypherProvider($token);
    }

    /**
     * Obtient le fournisseur
     *
     * @return BlockCypherProvider
     */
    public function getProvider(): BlockCypherProvider
    {
        return $this->provider;
    }

    /**
     * Obtient le fournisseur par défaut
     *
     * @return BalanceProviderInterface
     */
    public function getDefaultProvider(): BalanceProviderInterface
    {
        return $this->provider;
    }

    /**
     * Récupère le solde d'une adresse
     *
     * @param string $address Adresse Dogecoin
     * @return float Solde en DOGE
     */
    public function getBalance(string $address): float
    {
        return $this->provider->getBalance($address);
    }

    /**
     * Récupère les soldes de plusieurs adresses
     *
     * @param array<string> $addresses Adresses Dogecoin
     * @return array<string, float> Mapping address => solde
     */
    public function getBalances(array $addresses): array
    {
        return $this->provider->getBalances($addresses);
    }

    /**
     * Obtient les informations du fournisseur
     *
     * @return array<string, mixed>
     */
    public function getProviderInfo(): array
    {
        return [
            'name' => $this->provider->getName(),
            'available' => $this->provider->isAvailable(),
        ];
    }
}
