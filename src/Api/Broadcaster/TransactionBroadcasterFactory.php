<?php

declare(strict_types=1);

namespace DogeSweeper\Api\Broadcaster;

use DogeSweeper\Api\TransactionBroadcasterInterface;

/**
 * Agrégateur multi-broadcaster pour envoyer les transactions
 *
 * @package DogeSweeper\Api\Broadcaster
 */
class TransactionBroadcasterFactory
{
    /**
     * @var array<TransactionBroadcasterInterface> Broadcasters disponibles
     */
    private array $broadcasters = [];

    /**
     * @var TransactionBroadcasterInterface Broadcaster par défaut
     */
    private TransactionBroadcasterInterface $defaultBroadcaster;

    /**
     * Initialise le factory avec les broadcasters par défaut
     */
    public function __construct()
    {
        // Ajouter les broadcasters par défaut
        $this->addBroadcaster(new SoChainBroadcaster());
        $this->addBroadcaster(new DogechainBroadcaster());
        $this->addBroadcaster(new BlockCypherBroadcaster());

        $this->defaultBroadcaster = $this->broadcasters[0];
    }

    /**
     * Ajoute un broadcaster
     *
     * @param TransactionBroadcasterInterface $broadcaster Broadcaster
     * @return self
     */
    public function addBroadcaster(TransactionBroadcasterInterface $broadcaster): self
    {
        $this->broadcasters[] = $broadcaster;
        return $this;
    }

    /**
     * Définit le broadcaster par défaut
     *
     * @param string $name Nom du broadcaster
     * @return self
     */
    public function setDefaultBroadcaster(string $name): self
    {
        foreach ($this->broadcasters as $broadcaster) {
            if ($broadcaster->getName() === $name) {
                $this->defaultBroadcaster = $broadcaster;
                return $this;
            }
        }

        throw new \InvalidArgumentException(
            "Broadcaster '{$name}' not found"
        );
    }

    /**
     * Obtient le broadcaster par défaut
     *
     * @return TransactionBroadcasterInterface
     */
    public function getDefaultBroadcaster(): TransactionBroadcasterInterface
    {
        return $this->defaultBroadcaster;
    }

    /**
     * Obtient tous les broadcasters
     *
     * @return array<TransactionBroadcasterInterface>
     */
    public function getBroadcasters(): array
    {
        return $this->broadcasters;
    }

    /**
     * Obtient un broadcaster par son nom
     *
     * @param string $name Nom du broadcaster
     * @return TransactionBroadcasterInterface|null
     */
    public function getBroadcaster(string $name): ?TransactionBroadcasterInterface
    {
        foreach ($this->broadcasters as $broadcaster) {
            if ($broadcaster->getName() === $name) {
                return $broadcaster;
            }
        }
        return null;
    }

    /**
     * Broadcast une transaction avec le broadcaster par défaut
     *
     * @param string $txHex Transaction en hexadécimal
     * @return string ID de transaction
     */
    public function broadcast(string $txHex): string
    {
        return $this->defaultBroadcaster->broadcast($txHex);
    }

    /**
     * Broadcast une transaction avec fallback automatique
     *
     * @param string $txHex Transaction en hexadécimal
     * @return string ID de transaction
     * @throws \RuntimeException Si tous les broadcasters échouent
     */
    public function broadcastWithFallback(string $txHex): string
    {
        $lastError = null;

        foreach ($this->broadcasters as $broadcaster) {
            try {
                return $broadcaster->broadcast($txHex);
            } catch (\Exception $e) {
                $lastError = $e;
                continue;
            }
        }

        throw new \RuntimeException(
            "All broadcasters failed: {$lastError->getMessage()}"
        );
    }
}
