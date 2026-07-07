<?php

declare(strict_types=1);

namespace DogeSweeper\Api;

/**
 * Interface pour les fournisseurs de données blockchain
 *
 * @package DogeSweeper\Api
 */
interface BalanceProviderInterface
{
    /**
     * Récupère le solde d'une adresse
     *
     * @param string $address Adresse Dogecoin
     * @return float Solde en DOGE
     * @throws \RuntimeException
     */
    public function getBalance(string $address): float;

    /**
     * Récupère les soldes de plusieurs adresses
     *
     * @param array<string> $addresses Adresses Dogecoin
     * @return array<string, float> Mapping address => solde
     * @throws \RuntimeException
     */
    public function getBalances(array $addresses): array;

    /**
     * Obtient le nom du fournisseur
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Vérifie si le fournisseur est disponible
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
