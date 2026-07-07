<?php

declare(strict_types=1);

namespace DogeSweeper\Api;

/**
 * Interface pour les broadcasters de transactions
 *
 * @package DogeSweeper\Api
 */
interface TransactionBroadcasterInterface
{
    /**
     * Broadcast une transaction signée vers le réseau
     *
     * @param string $txHex Transaction en hexadécimal
     * @return string ID de transaction (TXID)
     * @throws \RuntimeException
     */
    public function broadcast(string $txHex): string;

    /**
     * Obtient le statut d'une transaction
     *
     * @param string $txid ID de transaction
     * @return array<string, mixed> Statut et infos
     * @throws \RuntimeException
     */
    public function getTransactionStatus(string $txid): array;

    /**
     * Obtient le nom du broadcaster
     *
     * @return string
     */
    public function getName(): string;
}
