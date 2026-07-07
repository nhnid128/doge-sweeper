<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Broadcaster;

use DogeSweeper\Api\TransactionBroadcasterInterface;

/**
 * Factory pour le broadcaster BlockCypher
 *
 * Gestion simplifiée avec BlockCypher comme seul broadcaster
 *
 * @package DogeSweeper\Api\Broadcaster
 */
class TransactionBroadcasterFactory
{
    /**
     * @var BlockCypherBroadcaster Instance du broadcaster
     */
    private BlockCypherBroadcaster $broadcaster;

    /**
     * Initialise le factory avec BlockCypher
     *
     * @param string|null $token Token API BlockCypher (optionnel)
     */
    public function __construct(?string $token = null)
    {
        $this->broadcaster = new BlockCypherBroadcaster($token);
    }

    /**
     * Obtient le broadcaster
     *
     * @return BlockCypherBroadcaster
     */
    public function getBroadcaster(): BlockCypherBroadcaster
    {
        return $this->broadcaster;
    }

    /**
     * Obtient le broadcaster par défaut
     *
     * @return TransactionBroadcasterInterface
     */
    public function getDefaultBroadcaster(): TransactionBroadcasterInterface
    {
        return $this->broadcaster;
    }

    /**
     * Broadcast une transaction
     *
     * @param string $txHex Transaction en hexadécimal
     * @return string ID de transaction
     */
    public function broadcast(string $txHex): string
    {
        return $this->broadcaster->broadcast($txHex);
    }

    /**
     * Obtient les informations du broadcaster
     *
     * @return array<string, mixed>
     */
    public function getBroadcasterInfo(): array
    {
        return [
            'name' => $this->broadcaster->getName(),
        ];
    }
}
