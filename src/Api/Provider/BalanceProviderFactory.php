<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Provider;

use DogeSweeper\Api\BalanceProviderInterface;

/**
 * Agrégateur multi-fournisseur pour la récupération de soldes
 *
 * Permet de:
 * - Basculer entre plusieurs fournisseurs en cas d'erreur
 * - Vérifier les données avec plusieurs sources
 * - Sélectionner automatiquement le meilleur fournisseur
 *
 * @package DogeSweeper\Api\Provider
 */
class BalanceProviderFactory
{
    /**
     * @var array<BalanceProviderInterface> Fournisseurs disponibles
     */
    private array $providers = [];

    /**
     * @var BalanceProviderInterface Fournisseur par défaut
     */
    private BalanceProviderInterface $defaultProvider;

    /**
     * Initialise le factory avec les fournisseurs par défaut
     */
    public function __construct()
    {
        // Ajouter les fournisseurs par défaut
        $this->addProvider(new SoChainProvider());
        $this->addProvider(new DogechainProvider());
        // BlockCypher en dernier car il a des limites de taux
        $this->addProvider(new BlockCypherProvider());

        $this->defaultProvider = $this->providers[0];
    }

    /**
     * Ajoute un fournisseur
     *
     * @param BalanceProviderInterface $provider Fournisseur
     * @return self
     */
    public function addProvider(BalanceProviderInterface $provider): self
    {
        $this->providers[] = $provider;
        return $this;
    }

    /**
     * Définit le fournisseur par défaut
     *
     * @param string $name Nom du fournisseur
     * @return self
     */
    public function setDefaultProvider(string $name): self
    {
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $name) {
                $this->defaultProvider = $provider;
                return $this;
            }
        }

        throw new \InvalidArgumentException(
            "Provider '{$name}' not found"
        );
    }

    /**
     * Obtient le fournisseur par défaut
     *
     * @return BalanceProviderInterface
     */
    public function getDefaultProvider(): BalanceProviderInterface
    {
        return $this->defaultProvider;
    }

    /**
     * Obtient tous les fournisseurs
     *
     * @return array<BalanceProviderInterface>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Obtient un fournisseur par son nom
     *
     * @param string $name Nom du fournisseur
     * @return BalanceProviderInterface|null
     */
    public function getProvider(string $name): ?BalanceProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $name) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * Récupère le solde avec le fournisseur par défaut
     *
     * @param string $address Adresse Dogecoin
     * @return float Solde en DOGE
     */
    public function getBalance(string $address): float
    {
        return $this->defaultProvider->getBalance($address);
    }

    /**
     * Récupère les soldes avec le fournisseur par défaut
     *
     * @param array<string> $addresses Adresses Dogecoin
     * @return array<string, float> Mapping address => solde
     */
    public function getBalances(array $addresses): array
    {
        return $this->defaultProvider->getBalances($addresses);
    }

    /**
     * Récupère le solde avec fallback automatique
     *
     * Essaie chaque fournisseur jusqu'à ce qu'un réussisse
     *
     * @param string $address Adresse Dogecoin
     * @return float Solde en DOGE
     * @throws \RuntimeException Si tous les fournisseurs échouent
     */
    public function getBalanceWithFallback(string $address): float
    {
        $lastError = null;

        foreach ($this->providers as $provider) {
            try {
                return $provider->getBalance($address);
            } catch (\Exception $e) {
                $lastError = $e;
                continue;
            }
        }

        throw new \RuntimeException(
            "All balance providers failed: {$lastError->getMessage()}"
        );
    }

    /**
     * Récupère les soldes avec fallback automatique
     *
     * @param array<string> $addresses Adresses Dogecoin
     * @return array<string, float> Mapping address => solde
     * @throws \RuntimeException Si tous les fournisseurs échouent
     */
    public function getBalancesWithFallback(array $addresses): array
    {
        $lastError = null;

        foreach ($this->providers as $provider) {
            try {
                return $provider->getBalances($addresses);
            } catch (\Exception $e) {
                $lastError = $e;
                continue;
            }
        }

        throw new \RuntimeException(
            "All balance providers failed: {$lastError->getMessage()}"
        );
    }

    /**
     * Récupère les soldes depuis plusieurs fournisseurs pour comparaison
     *
     * @param string $address Adresse Dogecoin
     * @return array<string, float> Mapping provider_name => solde
     */
    public function getBalanceComparison(string $address): array
    {
        $balances = [];

        foreach ($this->providers as $provider) {
            try {
                $balances[$provider->getName()] = $provider->getBalance($address);
            } catch (\Exception $e) {
                $balances[$provider->getName()] = null;
            }
        }

        return $balances;
    }

    /**
     * Trouve le meilleur fournisseur (le plus rapide et disponible)
     *
     * @return BalanceProviderInterface
     * @throws \RuntimeException Si aucun fournisseur n'est disponible
     */
    public function findBestProvider(): BalanceProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->isAvailable()) {
                return $provider;
            }
        }

        throw new \RuntimeException(
            "No balance provider is currently available"
        );
    }
}
